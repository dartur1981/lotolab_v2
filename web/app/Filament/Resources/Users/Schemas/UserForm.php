<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informações do Usuário')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->helperText(fn (string $operation): string => 
                                $operation === 'edit' ? 'Deixe em branco para manter a senha atual.' : ''
                            ),

                        Select::make('roles')
                            ->label('Papéis (Permissões Shield)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Select::make('loterias')
                            ->label('Loterias Autorizadas (Tenancy)')
                            ->relationship('loterias', 'nome')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull()
                            ->helperText('Defina quais loterias este usuário poderá gerenciar/visualizar no painel da aplicação.'),
                    ]),
            ]);
    }
}
