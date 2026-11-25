<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\RelationManagers;

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
use Illuminate\Database\Eloquent\Builder;

class JogosRelationManager extends RelationManager
{
    use \App\Filament\App\Concerns\HasJogosStatusCards;

    protected string $view = 'filament.app.relation-managers.jogos-com-status';

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

    public static function executeBackgroundBot(string $action, ?string $ids = null, string $model = 'megasena', ?int $fechamentoId = null): void
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

        $args = "bot:run " . escapeshellarg($action);
        if ($ids) {
            $args .= " --ids=" . escapeshellarg($ids);
        }
        if ($fechamentoId) {
            $args .= " --fechamento=" . escapeshellarg((string) $fechamentoId);
        }
        $args .= " --model=" . escapeshellarg($model);

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
            ->recordClasses(fn ($record) => ((string) $record->status === '4') ? 'bg-emerald-950/20 hover:bg-emerald-950/30' : null)
            ->columns([
                TextColumn::make('id')
                    ->label('Jogo')
                    ->alignCenter()
                    ->formatStateUsing(function ($record, $rowLoop) {
                        $num = 'J' . str_pad($rowLoop->iteration, 2, '0', STR_PAD_LEFT);
                        if ((string) $record->status === '4') {
                            return new \Illuminate\Support\HtmlString("<span style='display:inline-flex; align-items:center; justify-content:center; gap:0.35rem; font-weight:800; color:#34d399;'><span>{$num}</span><span style='font-size:0.625rem; background:#059669; color:#ffffff; padding:0.1rem 0.35rem; border-radius:9999px; font-weight:800; line-height:1;'>✓</span></span>");
                        }
                        return new \Illuminate\Support\HtmlString("<span style='font-weight:700; color:#9ca3af;'>{$num}</span>");
                    }),
                
                TextColumn::make('grupos')
                    ->label('Grupos')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->default('-'),
                
                \Filament\Tables\Columns\ViewColumn::make('dezenas')
                    ->label('Dezenas')
                    ->alignCenter()
                    ->view('filament.app.tables.columns.dezenas-jogo'),
                
                \Filament\Tables\Columns\ViewColumn::make('acertos')
                    ->label('Acertos')
                    ->alignCenter()
                    ->view('filament.app.tables.columns.acertos-jogo'),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->alignCenter()
                    ->formatStateUsing(function (string $state): \Illuminate\Support\HtmlString {
                        return match ($state) {
                            '4' => new \Illuminate\Support\HtmlString("<span style='display:inline-flex; align-items:center; gap:0.375rem; background:#064e3b; color:#6ee7b7; font-size:0.75rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid rgba(16, 185, 129, 0.4); box-shadow:0 1px 2px rgba(0,0,0,0.1);'><span style='width:0.45rem; height:0.45rem; border-radius:9999px; background:#34d399;'></span>Apostado</span>"),
                            '3' => new \Illuminate\Support\HtmlString("<span style='display:inline-flex; align-items:center; gap:0.375rem; background:#0c4a6e; color:#7dd3fc; font-size:0.75rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid rgba(14, 165, 233, 0.4);'><span style='width:0.45rem; height:0.45rem; border-radius:9999px; background:#38bdf8;'></span>Lançado</span>"),
                            '1' => new \Illuminate\Support\HtmlString("<span style='display:inline-flex; align-items:center; gap:0.375rem; background:#78350f; color:#fde68a; font-size:0.75rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid rgba(245, 158, 11, 0.4);'>Divergente</span>"),
                            default => new \Illuminate\Support\HtmlString("<span style='display:inline-flex; align-items:center; gap:0.375rem; background:#374151; color:#d1d5db; font-size:0.75rem; font-weight:700; padding:0.25rem 0.65rem; border-radius:0.375rem; border:1px solid #4b5563;'>Pendente</span>"),
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->options([
                        '0' => 'Pendente',
                        '1' => 'Divergente',
                        '3' => 'Lançado',
                        '4' => 'Apostado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'] ?? [];
                        if (!empty($values)) {
                            return $query->whereIn('status', $values);
                        }
                        return $query;
                    }),
                SelectFilter::make('acertos')
                    ->label('Acertos')
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->options(
                        collect(range(0, 6))->mapWithKeys(fn ($i) => [(string) $i => match ($i) {
                            4 => '4 Acertos (Quadra)',
                            5 => '5 Acertos (Quina)',
                            6 => '6 Acertos (Sena)',
                            default => "$i Acertos"
                        }])->toArray()
                    )
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'] ?? [];
                        if (!empty($values)) {
                            return $query->whereIn('acertos', $values);
                        }
                        return $query;
                    }),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('apostar_pendentes')
                    ->label('Apostar Pendentes')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->disabled(fn () => static::isBotRunning())
                    ->action(function ($livewire) {
                        $fechamento = $livewire->getOwnerRecord();
                        $pendentes = $fechamento->jogos()->whereIn('status', ['0', '1'])->pluck('id')->toArray();
                        if (empty($pendentes)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Nenhum jogo pendente para apostar.')
                                ->info()
                                ->send();
                            return;
                        }
                        try {
                            static::executeBackgroundBot('lancar', implode(',', $pendentes), 'megasena');
                            \Filament\Notifications\Notification::make()
                                ->title(count($pendentes) . ' jogos pendentes enviados para aposta!')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao lançar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                \Filament\Actions\Action::make('voltar_todos_pendente')
                    ->label('Voltar TODOS para pendente')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->action(function ($livewire) {
                        $fechamento = $livewire->getOwnerRecord();
                        $fechamento->jogos()->update([
                            'status' => 0, // 0 = Pendente
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Status de todos os jogos alterado para Pendente!')
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\Action::make('conferirCarrinho')
                    ->label('Conferir Carrinho')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->disabled(fn () => static::isBotRunning())
                    ->action(function ($livewire) {
                        try {
                            $fechamento = $livewire->getOwnerRecord();
                            static::executeBackgroundBot('conferir', null, 'megasena', (int) $fechamento->id);
                            \Filament\Notifications\Notification::make()
                                ->title('Conferência do carrinho iniciada!')
                                ->body('O robô está lendo e conciliando os jogos em background. Acompanhe nos logs e nos cards de status.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('Erro ao iniciar conferência')->body($e->getMessage())->danger()->send();
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
                            static::executeBackgroundBot('lancar', (string) $record->id, 'megasena');
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
                                static::executeBackgroundBot('lancar', implode(',', $ids), 'megasena');
                                
                                if (method_exists($livewire, 'deselectAllTableRecords')) {
                                    $livewire->deselectAllTableRecords();
                                }
                                
                                \Filament\Notifications\Notification::make()->title('Jogos enviados para execução em background!')->success()->send();
                            } catch (\Throwable $e) {
                                \Filament\Notifications\Notification::make()->title('Erro ao lançar')->body($e->getMessage())->danger()->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->poll('2s');
    }
}
