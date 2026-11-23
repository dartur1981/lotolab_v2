<?php

namespace App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\Pages;

use App\Filament\App\Resources\Lotofacil\LotofacilFechamentos\LotofacilFechamentoResource;

use Filament\Resources\Pages\ListRecords;

class ListLotofacilFechamentos extends ListRecords
{
    protected static string $resource = LotofacilFechamentoResource::class;

    protected function authorizeAccess(): void
    {
        // Acesso liberado
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label('Novo Fechamento')
                ->color('success')
                ->icon('heroicon-o-bolt')
                ->modalHeading('Gerar Fechamento da Lotofácil')
                ->modalDescription('Selecione um bolão da Lotofácil para gerar as cartelas de fechamento combinatório.')
                ->modalSubmitActionLabel('Gerar Jogos')
                ->form([
                    \Filament\Forms\Components\Select::make('bolao_id')
                        ->label('Bolão da Lotofácil')
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
                    try {
                        $service = new \App\Services\LotofacilFechamentoService();
                        $service->gerarFechamento((int) $data['bolao_id'], (int) $data['quantidade_jogos']);
                        \Filament\Notifications\Notification::make()
                            ->title('Fechamento gerado com sucesso!')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro ao gerar fechamento')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
