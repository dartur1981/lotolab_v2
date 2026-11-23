<?php

namespace App\Filament\App\Resources\Megasena\MegasenaFechamentos\Pages;

use App\Filament\App\Resources\Megasena\MegasenaFechamentos\MegasenaFechamentoResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListMegasenaFechamentos extends ListRecords
{
    protected static string $resource = MegasenaFechamentoResource::class;

    protected function authorizeAccess(): void
    {
        // Acesso liberado
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Novo Fechamento')
                ->color('success')
                ->icon('heroicon-o-bolt')
                ->modalHeading('Gerar Fechamento da Mega-Sena')
                ->modalDescription('Selecione um bolão da Mega-Sena para gerar as cartelas de fechamento combinatório.')
                ->modalSubmitActionLabel('Gerar Jogos')
                ->form([
                    Select::make('bolao_id')
                        ->label('Bolão da Mega-Sena')
                        ->options(function () {
                            return \App\Models\Megasena\Bolao::whereIn('status', [1, 2])
                                ->whereDoesntHave('fechamento')
                                ->pluck('nome', 'id');
                        })
                        ->required()
                        ->searchable(),
                    TextInput::make('quantidade_jogos')
                        ->label('Quantidade de Jogos (Cartelas)')
                        ->numeric()
                        ->default(10)
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new \App\Services\MegasenaFechamentoService();
                        $service->gerarFechamento((int) $data['bolao_id'], (int) $data['quantidade_jogos']);
                        Notification::make()
                            ->title('Fechamento da Mega-Sena gerado com sucesso!')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Erro ao gerar fechamento')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }
}
