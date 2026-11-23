<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class MegasenaFechamentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => \App\Filament\App\Resources\Megasena\MegasenaFechamentos\MegasenaFechamentoResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('bolao.nome')
                    ->label('Nome')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record): string => "Fechamento Mega-Sena – {$state}")
                    ->toggleable(),
                TextColumn::make('quantidade_jogos')
                    ->label('Jogos Gerados')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('dezenas')
                    ->label('Pool de Dezenas')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->toggleable(),
                TextColumn::make('resultado')
                    ->label('Resultado Oficial')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver Jogos')
                    ->color('success')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn ($record) => \App\Filament\App\Resources\Megasena\MegasenaFechamentos\MegasenaFechamentoResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
