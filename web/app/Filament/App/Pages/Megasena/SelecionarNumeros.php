<?php

namespace App\Filament\App\Pages\Megasena;

use App\Filament\Concerns\BelongsToMegasena;
use Filament\Pages\Page;
use App\Models\Megasena\Bolao;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class SelecionarNumeros extends Page implements HasForms
{
    use InteractsWithForms, BelongsToMegasena;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected static ?string $slug = 'megasena/selecionar-numeros';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Mega-Sena';
    protected static ?string $title = 'Selecionar Números';
    protected string $view = 'filament.app.pages.megasena.selecionar-numeros';

    public $bolaoAtivo = null;
    public $bolaoSelecionadoId = null;
    public $meusBoloes = [];
    public $minhasDezenas = [];
    public $dezenasOutros = [];
    public $statusUsuarios = [];
    public $tendencias = [];
    public bool $isLocked = false;

    public $stats = [
        'q1' => 0,
        'q2' => 0,
        'q3' => 0,
        'subs' => [],
        'pares' => 0,
        'impares' => 0,
        'soma' => 0,
        'quentes' => 0,
        'mornos' => 0,
        'frios' => 0,
    ];

    public function mount()
    {
        $this->loadTendencias();
        $this->loadMeusBoloes();
        if ($this->meusBoloes->count() > 0) {
            $this->bolaoSelecionadoId = $this->meusBoloes->first()->id;
            $this->entrarBolao($this->bolaoSelecionadoId);
        }
    }

    public function updatedBolaoSelecionadoId($value)
    {
        if ($value) {
            $this->entrarBolao($value);
        } else {
            $this->bolaoAtivo = null;
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('bolaoSelecionadoId')
                    ->label('Trocar de Bolão')
                    ->options($this->meusBoloes->pluck('nome', 'id'))
                    ->live(),
            ]);
    }

    public function loadTendencias()
    {
        try {
            $records = DB::connection('analytics_megasena')->select('SELECT dezena, temperatura FROM tendencias_dezenas_megasena');
        } catch (\Throwable $e) {
            try {
                $dbName = config('database.connections.analytics_megasena.database', 'lotolab_megasena_analytics_v2');
                $records = DB::select("SELECT dezena, temperatura FROM {$dbName}.tendencias_dezenas_megasena");
            } catch (\Throwable $e2) {
                $records = [];
            }
        }

        $this->tendencias = [];
        foreach ($records as $r) {
            $tempRaw = strtolower(trim($r->temperatura ?? ''));
            $temp = match($tempRaw) {
                'quente' => 'quente',
                'morna', 'morno' => 'morno',
                default => 'frio',
            };
            $this->tendencias[(int)$r->dezena] = $temp;
        }
    }

    public function loadMeusBoloes()
    {
        $this->meusBoloes = auth()->user()->megasenaBoloes()
            ->whereIn('status', [0, 1, 2])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function entrarBolao($bolaoId)
    {
        $this->bolaoAtivo = Bolao::find($bolaoId);
        if (!$this->bolaoAtivo) return;

        if (empty($this->tendencias)) {
            $this->loadTendencias();
        }

        $this->isLocked = ($this->bolaoAtivo->status == 2);

        $registro = DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $this->bolaoAtivo->id)
            ->where('user_id', auth()->id())
            ->first();

        $this->minhasDezenas = $registro && $registro->numeros_selecionados 
            ? json_decode($registro->numeros_selecionados, true) 
            : [];

        $this->carregarDezenasOutros();
        $this->calcularStats();
    }

    public function toggleNumero($numero)
    {
        if ($this->isLocked) {
            Notification::make()->title('Bolão encerrado.')->warning()->send();
            return;
        }

        $numero = (int) $numero;

        // Se já está selecionada por mim, desmarca imediatamente
        if (in_array($numero, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$numero]));

            DB::table('megasena_bolao_user')
                ->where('megasena_bolao_id', $this->bolaoAtivo->id)
                ->where('user_id', auth()->id())
                ->update([
                    'numeros_selecionados' => json_encode($this->minhasDezenas),
                    'updated_at' => now(),
                ]);

            $this->calcularStats();
            $this->verificarConclusaoBolao();
            return;
        }

        // Recarrega as dezenas dos outros participantes em tempo real para evitar concorrência
        $this->carregarDezenasOutros();

        $meuLimite = $this->bolaoAtivo->getLimiteDezenasForUser(auth()->id());

        if (count($this->minhasDezenas) >= $meuLimite) {
            Notification::make()->title("Limite de {$meuLimite} dezenas da sua cota atingido.")->warning()->send();
            return;
        }

        if (in_array($numero, $this->dezenasOutros)) {
            Notification::make()->title('Esta dezena já foi escolhida por outro participante.')->danger()->send();
            return;
        }

        // Validação da Quantidade Permitida por Quadrante no bolão
        if ($numero <= 20) {
            $maxQ = $this->bolaoAtivo->max_quadrante_1 ?? 2;
            if ($maxQ > 0 && $this->stats['q1'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 1 (01 a 20).")->warning()->send();
                return;
            }
        } elseif ($numero <= 40) {
            $maxQ = $this->bolaoAtivo->max_quadrante_2 ?? 2;
            if ($maxQ > 0 && $this->stats['q2'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 2 (21 a 40).")->warning()->send();
                return;
            }
        } else {
            $maxQ = $this->bolaoAtivo->max_quadrante_3 ?? 2;
            if ($maxQ > 0 && $this->stats['q3'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 3 (41 a 60).")->warning()->send();
                return;
            }
        }

        // Validação da Quantidade Permitida por Sub-Quadrante (Bloco 2x2 do volante) no bolão
        $linha = (int) floor(($numero - 1) / 10);
        $coluna = ($numero - 1) % 10;
        $subIndex = ((int) floor($linha / 2) * 5) + (int) floor($coluna / 2) + 1;
        $maxSub = $this->bolaoAtivo->max_sub_quadrante ?? 1;

        if ($maxSub > 0 && ($this->stats['subs'][$subIndex] ?? 0) >= $maxSub) {
            Notification::make()->title("Limite atingido: o Sub-Quadrante {$subIndex} já possui o máximo de {$maxSub} número(s) no bolão.")->warning()->send();
            return;
        }

        $this->minhasDezenas[] = $numero;
        sort($this->minhasDezenas);

        DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $this->bolaoAtivo->id)
            ->where('user_id', auth()->id())
            ->update([
                'numeros_selecionados' => json_encode($this->minhasDezenas),
                'updated_at' => now(),
            ]);

        $this->calcularStats();
        $this->verificarConclusaoBolao();
    }

    public function carregarDezenasOutros()
    {
        if (!$this->bolaoAtivo) return;

        $pivotRecords = DB::table('megasena_bolao_user')
            ->join('users', 'megasena_bolao_user.user_id', '=', 'users.id')
            ->where('megasena_bolao_id', $this->bolaoAtivo->id)
            ->select('megasena_bolao_user.*', 'users.name as user_name')
            ->orderBy('megasena_bolao_user.id')
            ->get();

        $userIds = $pivotRecords->pluck('user_id')->toArray();
        $qtd = count($userIds);
        $totalBolao = $this->bolaoAtivo->total_numeros;
        $porPessoa = $qtd > 0 ? (int) floor($totalBolao / $qtd) : 0;
        $sobra = $qtd > 0 ? (int) ($totalBolao % $qtd) : 0;
        $ultimoUser = end($userIds);

        $outros = [];
        $statusArray = [];

        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            $selecionadas = array_map('intval', $selecionadas);
            sort($selecionadas);

            $limite = ($record->user_id == $ultimoUser) ? ($porPessoa + $sobra) : $porPessoa;
            $isMe = ($record->user_id == auth()->id());

            $statusArray[] = [
                'user_id' => $record->user_id,
                'name' => $record->user_name,
                'is_me' => $isMe,
                'selecionadas' => $selecionadas,
                'limite' => $limite,
                'finalizado' => count($selecionadas) >= $limite,
            ];

            if ($isMe) {
                // Sincroniza minhas dezenas se foram alteradas
                $this->minhasDezenas = $selecionadas;
            } else {
                $outros = array_merge($outros, $selecionadas);
            }
        }

        $this->statusUsuarios = $statusArray;
        $this->dezenasOutros = array_map('intval', array_unique($outros));
        $this->calcularStats();
    }

    public function calcularStats()
    {
        $q1 = $q2 = $q3 = $pares = $soma = $quentes = $mornos = $frios = 0;
        $subs = array_fill(1, 15, 0);

        // Estatísticas pessoais (minhas dezenas)
        foreach ($this->minhasDezenas as $d) {
            $soma += $d;
            if ($d % 2 == 0) $pares++;

            $t = $this->tendencias[$d] ?? 'frio';
            if ($t === 'quente') $quentes++;
            elseif ($t === 'morno') $mornos++;
            else $frios++;
        }

        // Estatísticas consolidadas do bolão (minhas + dezenas dos outros participantes)
        $todasDezenas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));

        foreach ($todasDezenas as $d) {
            if ($d <= 20) {
                $q1++;
            } elseif ($d <= 40) {
                $q2++;
            } else {
                $q3++;
            }

            $linha = (int) floor(($d - 1) / 10);
            $coluna = ($d - 1) % 10;
            $subIndex = ((int) floor($linha / 2) * 5) + (int) floor($coluna / 2) + 1;

            if (isset($subs[$subIndex])) {
                $subs[$subIndex]++;
            }
        }

        $totalBolaoConfig = $this->bolaoAtivo->total_numeros ?? 60;
        $totalMarcadasGeral = count($todasDezenas);
        $percentualProgresso = ($totalBolaoConfig > 0) ? min(100, round(($totalMarcadasGeral / $totalBolaoConfig) * 100)) : 0;

        $this->stats = [
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'subs' => $subs,
            'pares' => $pares,
            'impares' => count($this->minhasDezenas) - $pares,
            'soma' => $soma,
            'quentes' => $quentes,
            'mornos' => $mornos,
            'frios' => $frios,
            'total_marcadas_geral' => $totalMarcadasGeral,
            'total_bolao_config' => $totalBolaoConfig,
            'progresso_percent' => $percentualProgresso,
        ];
    }

    public function verificarConclusaoBolao()
    {
        if ($this->bolaoAtivo->status == 2) return;

        $registros = DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $this->bolaoAtivo->id)
            ->get();

        $totalMarcadas = 0;
        foreach ($registros as $r) {
            $selecionadas = json_decode($r->numeros_selecionados, true) ?? [];
            $totalMarcadas += count($selecionadas);
        }

        if ($totalMarcadas >= $this->bolaoAtivo->total_numeros) {
            $this->bolaoAtivo->update(['status' => 2]);
            $this->isLocked = true;
            Notification::make()->title('Bolão Completo! Status atualizado para Encerrado.')->success()->send();
        }
    }
}
