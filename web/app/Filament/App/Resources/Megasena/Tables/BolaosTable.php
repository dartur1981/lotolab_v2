<?php

namespace App\Filament\App\Resources\Megasena\Tables;

use App\Models\Megasena\Bolao;
use App\Services\MegasenaFechamentoService;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

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
                    ->label('Dezenas (Pool)')
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
            ->recordActions([
                Action::make('gerar_fechamento')
                    ->label('Gerar Jogos')
                    ->color('success')
                    ->icon('heroicon-o-bolt')
                    ->modalHeading('Gerar Fechamento da Mega-Sena')
                    ->modalDescription('Gere as cartelas de 6 dezenas a partir do pool selecionado pelos participantes.')
                    ->modalSubmitActionLabel('Gerar Cartelas')
                    ->modalIcon('heroicon-o-sparkles')
                    ->form([
                        Fieldset::make('Configurações de Geração')
                            ->schema([
                                TextInput::make('quantidade_jogos')
                                    ->label('Quantidade de Jogos (Cartelas de 6 dezenas)')
                                    ->numeric()
                                    ->default(10)
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, Bolao $record) {
                        $service = new MegasenaFechamentoService();
                        $service->gerarFechamento($record->id, (int) $data['quantidade_jogos']);
                        Notification::make()
                            ->title('Jogos da Mega-Sena gerados com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->visible(function (Bolao $record): bool {
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
                    ->before(function (Bolao $record) {
                        $record->users()->detach();
                        if ($record->fechamento) {
                            $record->fechamento->delete();
                        }
                    }),
            ])
            ->toolbarActions([
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
