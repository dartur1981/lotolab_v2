<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Tables;


use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LotofacilFechamentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bolao.nome')
                    ->label('Nome')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record): string => "Fechamento – {$state}")
                    ->toggleable(),
                TextColumn::make('dezenas')
                    ->label('Dezenas')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->toggleable(),
                TextColumn::make('resultado')
                    ->label('Resultado')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Ver Jogos Gerados')
                    ->color('success')
                    ->icon('heroicon-o-list-bullet'),

                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
