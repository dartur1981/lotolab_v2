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
            ])
            ->actions([
                \Filament\Actions\Action::make('apostar')
                    ->label('Apostar')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status == '0')
                    ->disabled(fn () => file_exists(base_path('../logs/bot.lock')))
                    ->action(function ($record) {
                        try {
                            $lockFile = base_path('../logs/bot.lock');
                            file_put_contents($lockFile, 'running');

                            $artisan = base_path('artisan');
                            $command = 'start /B cmd /c "php ' . escapeshellarg($artisan) . ' bot:run lancar --ids=' . $record->id . '" > NUL 2> NUL';
                            pclose(popen($command, "r"));
                            
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
                        ->disabled(fn () => file_exists(base_path('../logs/bot.lock')))
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
                                $lockFile = base_path('../logs/bot.lock');
                                file_put_contents($lockFile, 'running');

                                $idsStr = implode(',', $ids);
                                $artisan = base_path('artisan');
                                $command = 'start /B cmd /c "php ' . escapeshellarg($artisan) . ' bot:run lancar --ids=' . escapeshellarg($idsStr) . '" > NUL 2> NUL';
                                pclose(popen($command, "r"));
                                
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
