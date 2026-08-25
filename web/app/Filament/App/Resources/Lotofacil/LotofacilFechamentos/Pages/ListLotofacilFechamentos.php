<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\LotofacilFechamentoResource;

use Filament\Resources\Pages\ListRecords;

class ListLotofacilFechamentos extends ListRecords
{
    protected static string $resource = LotofacilFechamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('gerar')
                ->label('Gerar do Bolão Encerrado')
                ->color('success')
                ->icon('heroicon-o-bolt')
                ->form([
                    \Filament\Forms\Components\Select::make('bolao_id')
                        ->label('Bolão')
                        ->options(function () {
                            // Pega bolões com status 2 (encerrado) ou 1 (ativo) que não possuem fechamento
                            return \App\Models\Lotofacil\Bolao::whereIn('status', [1, 2])
                                ->whereDoesntHave('fechamento')
                                ->pluck('nome', 'id');
                        })
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\TextInput::make('quantidade_jogos')
                        ->label('Quantidade de Jogos (Cartelas)')
                        ->numeric()
                        ->default(24)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $service = new \App\Services\LotofacilFechamentoService();
                    $service->gerarFechamento($data['bolao_id'], (int) $data['quantidade_jogos']);
                    \Filament\Notifications\Notification::make()
                        ->title('Fechamento gerado com sucesso!')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
