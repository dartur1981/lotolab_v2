<?php

namespace App\Filament\App\Resources\Lotofacil\Pages;

use App\Filament\App\Resources\Lotofacil\BolaoResource;
use App\Models\Lotofacil\Bolao;
use Filament\Resources\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;

class SelecoesBolao extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string $resource = BolaoResource::class;

    protected string $view = 'filament.app.resources.lotofacil.pages.selecoes-bolao';

    public Bolao $record;

    public function mount(Bolao $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string | Htmlable
    {
        return 'Seleções - ' . $this->record->nome;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\User::query()
                    ->select('users.*', 'lotofacil_bolao_user.numeros_selecionados')
                    ->join('lotofacil_bolao_user', 'users.id', '=', 'lotofacil_bolao_user.user_id')
                    ->where('lotofacil_bolao_user.lotofacil_bolao_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('bolao_id')
                    ->label('Número')
                    ->getStateUsing(fn () => $this->record->id),
                TextColumn::make('bolao_nome')
                    ->label('Nome do Bolão')
                    ->getStateUsing(fn () => $this->record->nome),
                TextColumn::make('bolao_concurso')
                    ->label('Concurso')
                    ->getStateUsing(fn () => $this->record->concurso_alvo ?? 'N/A'),
                TextColumn::make('bolao_data')
                    ->label('Data')
                    ->getStateUsing(fn () => $this->record->created_at->format('d/m/Y')),
                TextColumn::make('name')
                    ->label('Nome (Usuário)')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('numeros_selecionados')
                    ->label('Números Selecionados')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function ($record) {
                        $selecoes = is_string($record->numeros_selecionados) 
                            ? json_decode($record->numeros_selecionados, true) 
                            : $record->numeros_selecionados;
                        
                        if (empty($selecoes)) return [];
                        
                        return collect($selecoes)->map(fn($n) => str_pad($n, 2, '0', STR_PAD_LEFT))->toArray();
                    }),
            ])
            ->filters([
                SelectFilter::make('concurso')
                    ->label('Concurso')
                    ->options(fn () => Bolao::query()->whereNotNull('concurso_alvo')->distinct()->pluck('concurso_alvo', 'concurso_alvo')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            if ($this->record->concurso_alvo != $data['value']) {
                                $query->whereRaw('1 = 0');
                            }
                        }
                        return $query;
                    }),
                SelectFilter::make('usuario')
                    ->label('Usuário')
                    ->options(fn () => $this->record->users()->pluck('name', 'users.id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            $query->where('users.id', $data['value']);
                        }
                        return $query;
                    }),
                Filter::make('data')
                    ->label('Data')
                    ->form([
                        DatePicker::make('data_bolao')->label('Data do Bolão'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['data_bolao'])) {
                            $date = \Carbon\Carbon::parse($data['data_bolao'])->format('Y-m-d');
                            if ($this->record->created_at->format('Y-m-d') != $date) {
                                $query->whereRaw('1 = 0');
                            }
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Action::make('excluir')
                    ->label('Excluir')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Excluir Seleção')
                    ->modalDescription('Tem certeza que deseja excluir as seleções deste usuário no bolão? Esta ação não pode ser desfeita.')
                    ->modalSubmitActionLabel('Sim, excluir')
                    ->action(function ($record) {
                        $this->record->users()->detach($record->id);
                        Notification::make()
                            ->title('Seleção excluída')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
