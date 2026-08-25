<?php

namespace App\Filament\App\Resources\Lotofacil\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Table;

class BolaosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome do Bolão')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => match ($state) {
                        0 => 'Aguardando',
                        1 => 'Ativo',
                        2 => 'Encerrado',
                        3 => 'Apostado',
                        4 => 'Apurado',
                        default => 'Aguardando',
                    })
                    ->color(fn (?int $state): string => match ($state) {
                        0 => 'warning',
                        1 => 'success',
                        2 => 'danger',
                        3 => 'info',
                        4 => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('concurso_alvo')
                    ->label('Concurso')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('total_numeros')
                    ->label('Dezenas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Participantes')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('valor_cota')
                    ->label('Rateio (R$)')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->label('Total (R$)')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        0 => 'Aguardando',
                        1 => 'Ativo',
                        2 => 'Encerrado',
                        3 => 'Apostado',
                        4 => 'Apurado',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('concurso_alvo')
                    ->label('Concurso')
                    ->options(fn () => \App\Models\Lotofacil\Bolao::query()->whereNotNull('concurso_alvo')->distinct()->orderBy('concurso_alvo', 'desc')->pluck('concurso_alvo', 'concurso_alvo')->toArray())
                    ->searchable(),
                \Filament\Tables\Filters\SelectFilter::make('total_numeros')
                    ->label('Dezenas')
                    ->options(fn () => \App\Models\Lotofacil\Bolao::query()->whereNotNull('total_numeros')->distinct()->orderBy('total_numeros')->pluck('total_numeros', 'total_numeros')->toArray()),
            ])
            ->actions([
                Action::make('selecoes')
                    ->label('Seleções')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn (\App\Models\Lotofacil\Bolao $record): string => \App\Filament\App\Resources\Lotofacil\BolaoResource::getUrl('selecoes', ['record' => $record])),
                Action::make('gerar_fechamento')
                    ->label('Gerar do Bolão Encerrado')
                    ->color('success')
                    ->icon('heroicon-o-bolt')
                    ->modalHeading('Gerar Fechamento do Bolão')
                    ->modalDescription('Gere os jogos (cartelas) para este bolão encerrado de acordo com a quantidade informada.')
                    ->modalSubmitActionLabel('Gerar Cartelas')
                    ->modalIcon('heroicon-o-sparkles')
                    ->form([
                        \Filament\Schemas\Components\Fieldset::make('Configurações de Geração')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('quantidade_jogos')
                                    ->label('Quantidade de Jogos (Cartelas)')
                                    ->numeric()
                                    ->default(24)
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, \App\Models\Lotofacil\Bolao $record) {
                        $service = new \App\Services\LotofacilFechamentoService();
                        $service->gerarFechamento($record->id, (int) $data['quantidade_jogos']);
                        \Filament\Notifications\Notification::make()
                            ->title('Fechamento gerado com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->visible(function (\App\Models\Lotofacil\Bolao $record): bool {
                        if ($record->status !== 2) {
                            return false;
                        }
                        
                        if ($record->fechamento()->exists()) {
                            return false;
                        }
                        
                        if ($record->users()->count() === 0) {
                            return false;
                        }

                        foreach ($record->users as $user) {
                            if ($record->isPendingForUser($user->id)) {
                                return false;
                            }
                        }
                        
                        return true;
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (\App\Models\Lotofacil\Bolao $record) {
                        $record->users()->detach();
                        if ($record->fechamento) {
                            $record->fechamento->delete();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->users()->detach();
                                if ($record->fechamento) {
                                    $record->fechamento->delete();
                                }
                            }
                        }),
                ]),
            ]);
    }
}
