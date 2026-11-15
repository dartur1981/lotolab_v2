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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Mega-Sena';
    protected static ?string $title = 'Selecionar Números';
    protected string $view = 'filament.app.pages.megasena.selecionar-numeros';

    public $bolaoAtivo = null;
    public $bolaoSelecionadoId = null;
    public $meusBoloes = [];
    public $minhasDezenas = [];
    public $dezenasOutros = [];
    public bool $isLocked = false;

    public $stats = [
        'q1' => 0,
        'q2' => 0,
        'q3' => 0,
        'subs' => [],
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
        $this->meusBoloes = auth()->user()->megasenaBoloes()
            ->whereIn('status', [0, 1, 2])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function entrarBolao($bolaoId)
    {
        $this->bolaoAtivo = Bolao::find($bolaoId);
        if (!$this->bolaoAtivo) return;

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

        $meuLimite = $this->bolaoAtivo->getLimiteDezenasForUser(auth()->id());

        if (in_array($numero, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$numero]));
        } else {
            if (count($this->minhasDezenas) >= $meuLimite) {
                Notification::make()->title("Limite de {$meuLimite} dezenas atingido.")->warning()->send();
                return;
            }

            if (in_array($numero, $this->dezenasOutros)) {
                Notification::make()->title('Esta dezena já foi escolhida por outro participante.')->danger()->send();
                return;
            }

            // Validação da Quantidade Permitida por Quadrante
            if ($numero <= 20) {
                $maxQ = $this->bolaoAtivo->max_quadrante_1 ?? 2;
                if ($this->stats['q1'] >= $maxQ) {
                    Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 1 (01 a 20).")->warning()->send();
                    return;
                }
            } elseif ($numero <= 40) {
                $maxQ = $this->bolaoAtivo->max_quadrante_2 ?? 2;
                if ($this->stats['q2'] >= $maxQ) {
                    Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 2 (21 a 40).")->warning()->send();
                    return;
                }
            } else {
                $maxQ = $this->bolaoAtivo->max_quadrante_3 ?? 2;
                if ($this->stats['q3'] >= $maxQ) {
                    Notification::make()->title("Limite atingido: máximo de {$maxQ} número(s) permitido(s) no Quadrante 3 (41 a 60).")->warning()->send();
                    return;
                }
            }

            // Validação da Quantidade Permitida por Sub-Quadrante (Bloco 2x2 do volante)
            $linha = (int) floor(($numero - 1) / 10);
            $coluna = ($numero - 1) % 10;
            $subIndex = ((int) floor($linha / 2) * 5) + (int) floor($coluna / 2) + 1;
            $maxSub = $this->bolaoAtivo->max_sub_quadrante ?? 1;

            if ($maxSub > 0 && ($this->stats['subs'][$subIndex] ?? 0) >= $maxSub) {
                Notification::make()->title("Limite atingido: máximo de {$maxSub} número(s) permitido(s) no Sub-Quadrante {$subIndex}.")->warning()->send();
                return;
            }

            $this->minhasDezenas[] = (int) $numero;
            sort($this->minhasDezenas);
        }

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
        $outros = DB::table('megasena_bolao_user')
            ->where('megasena_bolao_id', $this->bolaoAtivo->id)
            ->where('user_id', '!=', auth()->id())
            ->pluck('numeros_selecionados');

        $arr = [];
        foreach ($outros as $json) {
            if ($json) {
                $decoded = json_decode($json, true) ?? [];
                $arr = array_merge($arr, $decoded);
            }
        }
        $this->dezenasOutros = array_unique($arr);
    }

    public function calcularStats()
    {
        $q1 = $q2 = $q3 = $pares = $soma = 0;
        $subs = array_fill(1, 15, 0);

        foreach ($this->minhasDezenas as $d) {
            $soma += $d;
            if ($d % 2 == 0) $pares++;

            if ($d <= 20) {
                $q = 1;
                $q1++;
            } elseif ($d <= 40) {
                $q = 2;
                $q2++;
            } else {
                $q = 3;
                $q3++;
            }

            $linha = (int) floor(($d - 1) / 10);
            $coluna = ($d - 1) % 10;
            $subIndex = ((int) floor($linha / 2) * 5) + (int) floor($coluna / 2) + 1;

            if (isset($subs[$subIndex])) {
                $subs[$subIndex]++;
            }
        }

        $this->stats = [
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'subs' => $subs,
            'pares' => $pares,
            'impares' => count($this->minhasDezenas) - $pares,
            'soma' => $soma,
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
