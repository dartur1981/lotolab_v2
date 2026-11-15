<?php

namespace App\Filament\App\Resources\Megasena\RelationManagers;

use App\Models\Megasena\Bolao;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ParticipantesRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Seleções dos Participantes';
    protected static ?string $modelLabel = 'Participante';
    protected static ?string $pluralModelLabel = 'Participantes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $bolao = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Participante')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email ?? ''),

                TextColumn::make('cota_limite')
                    ->label('Cota (Meta)')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(function (User $record) use ($bolao) {
                        $limite = $bolao->getLimiteDezenasForUser($record->id);
                        return "{$limite} dezenas";
                    }),

                TextColumn::make('numeros_selecionados')
                    ->label('Dezenas Escolhidas')
                    ->getStateUsing(function (User $record) {
                        $selecoes = is_string($record->pivot->numeros_selecionados)
                            ? json_decode($record->pivot->numeros_selecionados, true)
                            : ($record->pivot->numeros_selecionados ?? []);

                        if (empty($selecoes)) {
                            return 'Nenhuma dezena selecionada';
                        }

                        sort($selecoes);
                        return collect($selecoes)->map(fn ($n) => str_pad($n, 2, '0', STR_PAD_LEFT))->implode(' · ');
                    })
                    ->badge()
                    ->color(function (User $record) use ($bolao): string {
                        $selecoes = is_string($record->pivot->numeros_selecionados)
                            ? json_decode($record->pivot->numeros_selecionados, true)
                            : ($record->pivot->numeros_selecionados ?? []);
                        $limite = $bolao->getLimiteDezenasForUser($record->id);

                        if (empty($selecoes)) return 'danger';
                        if (count($selecoes) >= $limite) return 'success';
                        return 'warning';
                    })
                    ->searchable(false),

                TextColumn::make('status_selecao')
                    ->label('Status')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (User $record) use ($bolao): string {
                        $selecoes = is_string($record->pivot->numeros_selecionados)
                            ? json_decode($record->pivot->numeros_selecionados, true)
                            : ($record->pivot->numeros_selecionados ?? []);
                        $limite = $bolao->getLimiteDezenasForUser($record->id);
                        $qtd = count($selecoes);

                        if ($qtd === 0) {
                            return "Pendente (0/{$limite})";
                        }
                        if ($qtd >= $limite) {
                            return "Concluído ({$qtd}/{$limite})";
                        }
                        $falta = $limite - $qtd;
                        return "Faltam {$falta} ({$qtd}/{$limite})";
                    })
                    ->color(function (User $record) use ($bolao): string {
                        $selecoes = is_string($record->pivot->numeros_selecionados)
                            ? json_decode($record->pivot->numeros_selecionados, true)
                            : ($record->pivot->numeros_selecionados ?? []);
                        $limite = $bolao->getLimiteDezenasForUser($record->id);
                        $qtd = count($selecoes);

                        if ($qtd === 0) return 'danger';
                        if ($qtd >= $limite) return 'success';
                        return 'warning';
                    }),
            ])
            ->recordActions([
                Action::make('definir_dezenas')
                    ->label('Definir Dezenas')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading(fn (User $record) => "Definir Dezenas - {$record->name}")
                    ->modalDescription(function (User $record) use ($bolao) {
                        $limite = $bolao->getLimiteDezenasForUser($record->id);
                        return "Escolha exatamente {$limite} dezenas para este participante no Bolão '{$bolao->nome}'.";
                    })
                    ->modalSubmitActionLabel('Salvar Dezenas')
                    ->form(function (User $record) use ($bolao) {
                        $limite = $bolao->getLimiteDezenasForUser($record->id);
                        $opcoes = [];
                        for ($i = 1; $i <= 60; $i++) {
                            $numStr = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $opcoes[$i] = "Dezena {$numStr}";
                        }

                        $selecoesAtuais = is_string($record->pivot->numeros_selecionados)
                            ? json_decode($record->pivot->numeros_selecionados, true)
                            : ($record->pivot->numeros_selecionados ?? []);

                        return [
                            Select::make('dezenas')
                                ->label("Dezenas Selecionadas (Meta: {$limite})")
                                ->multiple()
                                ->options($opcoes)
                                ->default($selecoesAtuais)
                                ->maxItems($limite)
                                ->required()
                                ->helperText("Selecione até {$limite} dezenas para o participante."),
                        ];
                    })
                    ->action(function (array $data, User $record) use ($bolao) {
                        $dezenas = array_map('intval', $data['dezenas'] ?? []);
                        sort($dezenas);

                        DB::table('megasena_bolao_user')
                            ->where('megasena_bolao_id', $bolao->id)
                            ->where('user_id', $record->id)
                            ->update([
                                'numeros_selecionados' => json_encode($dezenas),
                                'updated_at' => now(),
                            ]);

                        Notification::make()
                            ->title('Dezenas Atualizadas!')
                            ->body("As dezenas de {$record->name} foram salvas com sucesso.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
