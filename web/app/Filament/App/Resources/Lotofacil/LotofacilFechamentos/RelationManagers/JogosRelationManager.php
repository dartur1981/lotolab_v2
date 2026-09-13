<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JogosRelationManager extends RelationManager
{
    protected static string $relationship = 'jogos';

    #[\Livewire\Attributes\On('refresh-jogos-table')]
    public function refreshTable(): void
    {
        // Livewire will automatically refresh the component
    }

    public static function isBotRunning(): bool
    {
        $lockFile = base_path('../logs/bot.lock');
        if (!file_exists($lockFile)) {
            return false;
        }

        // Se o arquivo tiver mais de 5 minutos, considera travado/órfão e remove
        if ((time() - filemtime($lockFile)) > 300) {
            @unlink($lockFile);
            return false;
        }

        return true;
    }

    public static function getPhpBinary(): string
    {
        if (class_exists(\Symfony\Component\Process\PhpExecutableFinder::class)) {
            $finder = new \Symfony\Component\Process\PhpExecutableFinder();
            $binary = $finder->find(false);
            if ($binary) {
                return $binary;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return 'php';
        }

        foreach (['/usr/local/bin/php', '/usr/bin/php', '/bin/php'] as $path) {
            if (@is_executable($path)) {
                return $path;
            }
        }

        return 'php';
    }

    public static function executeBackgroundBot(string $action, string $ids, string $model = 'lotofacil'): void
    {
        $logDir = base_path('../logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $lockFile = base_path('../logs/bot.lock');
        @file_put_contents($lockFile, 'running');
        @chmod($lockFile, 0666);

        $artisan = base_path('artisan');
        $phpBinary = static::getPhpBinary();
        $logPath = base_path('../logs/artisan_bot.log');

        $args = "bot:run " . escapeshellarg($action) . " --ids=" . escapeshellarg($ids) . " --model=" . escapeshellarg($model);

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'start /B cmd /c "' . escapeshellcmd($phpBinary) . ' ' . escapeshellarg($artisan) . ' ' . $args . '" > NUL 2> NUL';
            \Illuminate\Support\Facades\Log::info("Disparando robô (Windows): {$cmd}");
            pclose(popen($cmd, "r"));
        } else {
            $cmd = "nohup " . escapeshellcmd($phpBinary) . " " . escapeshellarg($artisan) . " " . $args . " >> " . escapeshellarg($logPath) . " 2>&1 &";
            \Illuminate\Support\Facades\Log::info("Disparando robô (Linux): {$cmd}");
            exec($cmd);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dezenas')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('Jogo')
                    ->formatStateUsing(fn ($rowLoop) => 'J' . str_pad($rowLoop->iteration, 2, '0', STR_PAD_LEFT)),
                
                TextColumn::make('grupos')
                    ->label('Grupos')
                    ->default('M1: G1+G2+G3')
                    ->color('gray'),
                
                \Filament\Tables\Columns\ViewColumn::make('dezenas')
                    ->label('Dezenas')
                    ->view('filament.app.tables.columns.dezenas-jogo'),
                
                \Filament\Tables\Columns\ViewColumn::make('acertos')
                    ->label('Acertos')
                    ->view('filament.app.tables.columns.acertos-jogo')
                    ->alignCenter(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'gray',
                        '1' => 'warning',
                        '3' => 'info',
                        '4' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => 'Pendente',
                        '1' => 'Divergente',
                        '3' => 'Lançado',
                        '4' => 'Apostado',
                        default => 'Desconhecido',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        '0' => 'Pendente',
                        '1' => 'Divergente',
                        '3' => 'Lançado',
                        '4' => 'Apostado',
                    ]),
                SelectFilter::make('acertos')
                    ->label('Acertos')
                    ->multiple()
                    ->options(
                        collect(range(0, 15))->mapWithKeys(fn ($i) => [(string) $i => $i === 1 ? '1 Acerto' : "$i Acertos"])->toArray()
                    ),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('voltar_todos_pendente')
                    ->label('Voltar TODOS para pendente')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->action(function ($livewire) {
                        // $livewire represents the RelationManager instance
                        $fechamento = $livewire->getOwnerRecord();
                        
                        $fechamento->jogos()->update([
                            'status' => 0, // 0 = Pendente
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Status de todos os jogos alterado para Pendente!')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('irParaCarrinho')
                    ->label('Ir para Carrinho')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('gray')
                    ->action(function (\App\Services\LotofacilBotService $bot) {
                        try {
                            $sucesso = $bot->irParaCarrinho();
                            if ($sucesso) {
                                \Filament\Notifications\Notification::make()->title('Navegou para o carrinho!')->success()->send();
                            } else {
                                \Filament\Notifications\Notification::make()->title('Erro')->body('Não foi possível navegar (botão não encontrado).')->danger()->send();
                            }
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('Erro')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\Action::make('lerEConciliar')
                    ->label('Ler Carrinho')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->action(function (\App\Services\LotofacilBotService $bot) {
                        try {
                            $jogosLidos = $bot->lerCarrinho();
                            
                            if (empty($jogosLidos)) {
                                \Filament\Notifications\Notification::make()->title('Nenhum jogo lido no carrinho.')->warning()->send();
                                return;
                            }
                            \Filament\Notifications\Notification::make()->title(count($jogosLidos) . ' jogos lidos.')->success()->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('Erro ao ler')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\Action::make('destravar_robo')
                    ->label('Destravar Robô')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->visible(fn () => static::isBotRunning())
                    ->requiresConfirmation()
                    ->modalHeading('Destravar Robô')
                    ->modalDescription('O robô está marcado como em execução. Deseja remover o bloqueio e liberar as ações de aposta?')
                    ->action(function () {
                        $lockFile = base_path('../logs/bot.lock');
                        if (file_exists($lockFile)) {
                            @unlink($lockFile);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Robô destravado com sucesso!')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('apostar')
                    ->label('Apostar')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn ($record) => in_array((string) $record->status, ['0', '1']))
                    ->disabled(fn () => static::isBotRunning())
                    ->action(function ($record) {
                        try {
                            static::executeBackgroundBot('lancar', (string) $record->id, 'lotofacil');
                            \Filament\Notifications\Notification::make()->title('Aposta enviada para execução em background!')->success()->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('Erro ao lançar')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\Action::make('voltar_pendente')
                    ->label('Voltar para pendente')
                    ->icon('heroicon-o-home')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status != 0)
                    ->action(function ($record) {
                        $record->update(['status' => '0']);
                        \Filament\Notifications\Notification::make()
                            ->title('Jogo alterado para Pendente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('apostar_selecionados')
                        ->label('Apostar Selecionados')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->disabled(fn () => static::isBotRunning())
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, $livewire = null) {
                            $validRecords = $records->filter(function ($record) {
                                return in_array((string) $record->status, ['0', '1']);
                            });
                            
                            $ids = $validRecords->pluck('id')->toArray();
                            if (empty($ids)) {
                                \Filament\Notifications\Notification::make()->title('Nenhum jogo selecionado é válido para aposta (apenas Pendente ou Divergente).')->warning()->send();
                                return;
                            }
                            
                            if (count($ids) < $records->count()) {
                                $ignorados = $records->count() - count($ids);
                                \Filament\Notifications\Notification::make()->title("{$ignorados} jogos ignorados por já estarem Lançados ou Apostados.")->info()->send();
                            }

                            try {
                                static::executeBackgroundBot('lancar', implode(',', $ids), 'lotofacil');
                                
                                if (method_exists($livewire, 'deselectAllTableRecords')) {
                                    $livewire->deselectAllTableRecords();
                                }
                                
                                \Filament\Notifications\Notification::make()->title('Jogos enviados para execução em background!')->success()->send();
                            } catch (\Throwable $e) {
                                \Filament\Notifications\Notification::make()->title('Erro ao lançar')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->poll('2s');
    }
}
