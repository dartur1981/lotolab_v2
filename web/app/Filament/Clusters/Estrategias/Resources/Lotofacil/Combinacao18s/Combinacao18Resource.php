<?php

namespace App\Filament\Clusters\Estrategias\Resources\Lotofacil\Combinacao18s;

use App\Filament\Clusters\Estrategias\EstrategiasCluster;
use App\Filament\Clusters\Estrategias\Resources\Lotofacil\Combinacao18s\Pages\ManageCombinacao18s;
use App\Models\Lotofacil\Combinacao18;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class Combinacao18Resource extends Resource
{
    protected static ?string $model = Combinacao18::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static bool $isScopedToTenant = false;

    protected static ?string $cluster = \App\Filament\Clusters\Estrategias\EstrategiasCluster::class;
    
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Combinação de 18 Dezenas';
    protected static ?string $pluralModelLabel = 'Combinações de 18 Dezenas';
    protected static ?string $navigationLabel = 'Combinações de 18';

    protected static ?string $recordTitleAttribute = 'dezenas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dezenas'),
                TextInput::make('pares')->numeric(),
                TextInput::make('impares')->numeric(),
                TextInput::make('primos')->numeric(),
                TextInput::make('fibonacci')->numeric(),
                TextInput::make('moldura')->numeric(),
                TextInput::make('miolo')->numeric(),
                TextInput::make('soma')->numeric(),
                TextInput::make('acertos_15')->numeric(),
                TextInput::make('acertos_14')->numeric(),
                TextInput::make('acertos_13')->numeric(),
                TextInput::make('acertos_12')->numeric(),
                TextInput::make('acertos_11')->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('dezenas')
            ->columns([
                TextColumn::make('dezenas')
                    ->label('Dezenas (18)')
                    ->searchable()
                    ->badge()
                    ->separator(',')
                    ->color('info'),
                TextColumn::make('score')
                    ->label('Score (0-100)')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('repetidas_ant1')
                    ->label('Rep. -1')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('repetidas_ant2')
                    ->label('Rep. -2')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('repetidas_ant3')
                    ->label('Rep. -3')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('pares')
                    ->label('Pares')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('impares')
                    ->label('Ímpares')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('primos')
                    ->label('Primos')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fibonacci')
                    ->label('Fibo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('moldura')
                    ->label('Moldura')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('miolo')
                    ->label('Miolo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('soma')
                    ->label('Soma')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Acertos na Simulação (Concurso 9999)
                TextColumn::make('acertos_15')
                    ->label('Sim. 15 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_14')
                    ->label('Sim. 14 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_13')
                    ->label('Sim. 13 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_12')
                    ->label('Sim. 12 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('acertos_11')
                    ->label('Sim. 11 Pts')
                    ->numeric()
                    ->sortable(),

                // Histórico Real Acumulado (Todos os Concursos Oficiais)
                TextColumn::make('historico_15')
                    ->label('Hist. 15 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('historico_14')
                    ->label('Hist. 14 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('historico_13')
                    ->label('Hist. 13 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('historico_12')
                    ->label('Hist. 12 Pts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('historico_11')
                    ->label('Hist. 11 Pts')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('score', 'desc')
            ->filters([
                \Filament\Tables\Filters\Filter::make('acertos_15')
                    ->label('Simulação 9999: 15 Pts')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->where('acertos_15', '>', 0)),
                \Filament\Tables\Filters\Filter::make('acertos_14')
                    ->label('Simulação 9999: 14 Pts')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->where('acertos_14', '>', 0)),
                \Filament\Tables\Filters\Filter::make('historico_15')
                    ->label('Histórico Real: Fez 15 Pts')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->where('historico_15', '>', 0)),
                \Filament\Tables\Filters\Filter::make('historico_14')
                    ->label('Histórico Real: Fez 14 Pts')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->where('historico_14', '>', 0)),
            ])
            ->actions([
                \Filament\Actions\Action::make('gerar_fechamento')
                    ->label('Gerar Fechamento')
                    ->color('success')
                    ->icon('heroicon-o-bolt')
                    ->modalHeading('Gerar Fechamento Estratégico')
                    ->modalDescription('Gere os jogos otimizados para esta combinação de 18 dezenas.')
                    ->modalSubmitActionLabel('Gerar Jogos')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('quantidade_jogos')
                            ->label('Quantidade de Jogos')
                            ->numeric()
                            ->default(15)
                            ->required(),
                    ])
                    ->action(function (array $data, \App\Models\Lotofacil\Combinacao18 $record) {
                        $dezenas_str = str_replace('-', ',', $record->dezenas);
                        $dezenas = array_map('intval', explode(',', $dezenas_str));

                        $service = new \App\Services\EstrategiaFechamentoService();
                        $fechamento = $service->gerarFechamento($dezenas, (int) $data['quantidade_jogos']);

                        \Filament\Notifications\Notification::make()
                            ->title('Fechamento gerado com sucesso!')
                            ->body("Foram criados {$data['quantidade_jogos']} jogos com pontuação.")
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                \Filament\Actions\ExportAction::make()
                    ->exporter(\App\Filament\Exports\Combinacao18Exporter::class)
                    ->label('Exportar (Fila)')
                    ->chunkSize(2500),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\ExportBulkAction::make()
                        ->exporter(\App\Filament\Exports\Combinacao18Exporter::class)
                        ->label('Exportar Selecionados')
                        ->chunkSize(2500),
                ]),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCombinacao18s::route('/'),
        ];
    }
}
