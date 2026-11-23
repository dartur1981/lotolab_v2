<?php

namespace App\Filament\App\Pages\Quina;

use App\Filament\Concerns\BelongsToQuina;
use Filament\Pages\Page;
use App\Models\Quina\Bolao;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class SelecionarNumeros extends Page implements HasForms
{
    use InteractsWithForms, BelongsToQuina;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected static ?string $slug = 'quina/selecionar-numeros';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Quina';
    protected static ?string $title = 'Selecionar Números';
    protected string $view = 'filament.app.pages.quina.selecionar-numeros';

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
        'q4' => 0,
        'quentes' => 0,
        'mornos' => 0,
        'frios' => 0,
        'pares' => 0,
        'impares' => 0,
        'soma' => 0,
    ];

    public function mount()
    {
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

    public function loadMeusBoloes()
    {
        $this->meusBoloes = auth()->user()->quinaBoloes()
            ->whereIn('status', [0, 1, 2])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function loadTendencias()
    {
        try {
            $records = DB::connection('analytics_quina')->select('SELECT dezena, temperatura FROM tendencias_dezenas_quina');
        } catch (\Throwable $e) {
            try {
                $dbName = config('database.connections.analytics_quina.database', 'lotolab_quina_analytics_v2');
                $records = DB::select("SELECT dezena, temperatura FROM {$dbName}.tendencias_dezenas_quina");
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

    public function entrarBolao($bolaoId)
    {
        $this->bolaoAtivo = Bolao::find($bolaoId);
        if (!$this->bolaoAtivo) return;

        if (empty($this->tendencias)) {
            $this->loadTendencias();
        }

        $this->isLocked = ($this->bolaoAtivo->status == 2);

        $registro = DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->bolaoAtivo->id)
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

        // Se já está selecionada por mim, desmarca
        if (in_array($numero, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$numero]));

            DB::table('quina_bolao_user')
                ->where('quina_bolao_id', $this->bolaoAtivo->id)
                ->where('user_id', auth()->id())
                ->update([
                    'numeros_selecionados' => json_encode($this->minhasDezenas),
                    'updated_at' => now(),
                ]);

            $this->calcularStats();
            $this->verificarConclusaoBolao();
            return;
        }

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

        // Validação da Quantidade Permitida por Quadrante (4 Quadrantes)
        if ($numero <= 20) {
            $maxQ = $this->bolaoAtivo->max_quadrante_1 ?? 3;
            if ($maxQ > 0 && $this->stats['q1'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 1 (01 a 20).")->warning()->send();
                return;
            }
        } elseif ($numero <= 40) {
            $maxQ = $this->bolaoAtivo->max_quadrante_2 ?? 3;
            if ($maxQ > 0 && $this->stats['q2'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 2 (21 a 40).")->warning()->send();
                return;
            }
        } elseif ($numero <= 60) {
            $maxQ = $this->bolaoAtivo->max_quadrante_3 ?? 3;
            if ($maxQ > 0 && $this->stats['q3'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 3 (41 a 60).")->warning()->send();
                return;
            }
        } else {
            $maxQ = $this->bolaoAtivo->max_quadrante_4 ?? 3;
            if ($maxQ > 0 && $this->stats['q4'] >= $maxQ) {
                Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 4 (61 a 80).")->warning()->send();
                return;
            }
        }

        $this->minhasDezenas[] = $numero;
        sort($this->minhasDezenas);

        DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->bolaoAtivo->id)
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

        $pivotRecords = DB::table('quina_bolao_user')
            ->join('users', 'quina_bolao_user.user_id', '=', 'users.id')
            ->where('quina_bolao_id', $this->bolaoAtivo->id)
            ->select('quina_bolao_user.*', 'users.name as user_name')
            ->orderBy('quina_bolao_user.id')
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
        $q1 = $q2 = $q3 = $q4 = $pares = $soma = 0;
        $quentes = $mornos = $frios = 0;

        foreach ($this->minhasDezenas as $d) {
            $soma += $d;
            if ($d % 2 == 0) $pares++;

            $temp = $this->tendencias[$d] ?? 'frio';
            if ($temp === 'quente') $quentes++;
            elseif ($temp === 'morno') $mornos++;
            else $frios++;
        }

        $todasDezenas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));

        foreach ($todasDezenas as $d) {
            if ($d <= 20) {
                $q1++;
            } elseif ($d <= 40) {
                $q2++;
            } elseif ($d <= 60) {
                $q3++;
            } else {
                $q4++;
            }
        }

        $totalBolaoConfig = $this->bolaoAtivo->total_numeros ?? 80;
        $totalMarcadasGeral = count($todasDezenas);
        $percentualProgresso = ($totalBolaoConfig > 0) ? min(100, round(($totalMarcadasGeral / $totalBolaoConfig) * 100)) : 0;

        $this->stats = [
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'quentes' => $quentes,
            'mornos' => $mornos,
            'frios' => $frios,
            'pares' => $pares,
            'impares' => count($this->minhasDezenas) - $pares,
            'soma' => $soma,
            'total_marcadas_geral' => $totalMarcadasGeral,
            'total_bolao_config' => $totalBolaoConfig,
            'progresso_percent' => $percentualProgresso,
        ];
    }

    public function verificarConclusaoBolao()
    {
        if ($this->bolaoAtivo->status == 2) return;

        $registros = DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->bolaoAtivo->id)
            ->get();

        $totalMarcadas = 0;
        foreach ($registros as $r) {
            $selecionadas = json_decode($r->numeros_selecionados, true) ?? [];
            $totalMarcadas += count($selecionadas);
        }

        if ($totalMarcadas >= $this->bolaoAtivo->total_numeros) {
            $this->bolaoAtivo->update(['status' => 2]);
            $this->isLocked = true;
            Notification::make()->title('Bolão da Quina Completo! Status atualizado para Encerrado.')->success()->send();
        }
    }
}
