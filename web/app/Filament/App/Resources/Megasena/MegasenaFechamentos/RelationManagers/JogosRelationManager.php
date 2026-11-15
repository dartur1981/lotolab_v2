<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
                        collect(range(0, 6))->mapWithKeys(fn ($i) => [(string) $i => match ($i) {
                            4 => '4 Acertos (Quadra)',
                            5 => '5 Acertos (Quina)',
                            6 => '6 Acertos (Sena)',
                            default => "$i Acertos"
                        }])->toArray()
                    ),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('voltar_todos_pendente')
                    ->label('Voltar TODOS para pendente')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->action(function ($livewire) {
                        $fechamento = $livewire->getOwnerRecord();
                        $fechamento->jogos()->update(['status' => 0]);
                        \Filament\Notifications\Notification::make()
                            ->title('Status de todos os jogos alterado para Pendente!')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
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
