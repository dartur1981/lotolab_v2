<?php

namespace App\Filament\App\Pages\Lotofacil;

use Filament\Pages\Page;
use App\Models\Lotofacil\Bolao;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;

class SelecionarNumeros extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
    protected static ?string $title = 'Selecionar Números';
    protected string $view = 'filament.app.pages.lotofacil.selecionar-numeros';

    public $bolaoAtivo = null;
    public $bolaoSelecionadoId = null;
    public $meusBoloes = [];
    public $minhasDezenas = [];
    public $dezenasOutros = [];
    public $tendencias = [];
    public bool $isLocked = false;
    public ?array $sugestaoAtual = null;

    // Metadados estatísticos calculados em tempo real
    public $stats = [
        'moldura' => 0,
        'miolo' => 0,
        'linhas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        'colunas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
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

    public function form($form)
    {
        return $form
            ->schema([
                Select::make('bolaoSelecionadoId')
                    ->label('Selecione o Bolão')
                    ->options($this->meusBoloes->pluck('nome', 'id'))
                    ->searchable()
                    ->live()
                    ->placeholder('Pesquise pelo nome do bolão...')
            ]);
    }

    public function loadMeusBoloes()
    {
        $userId = auth()->id();
        $this->meusBoloes = Bolao::whereHas('users', function($q) use ($userId) {
            $q->where('users.id', $userId);
        })->get();
    }

    public function entrarBolao($id)
    {
        $this->bolaoAtivo = Bolao::with('users')->find($id);
        $this->loadTendencias();
        $this->refreshBoard();
    }

    public function voltar()
    {
        $this->bolaoAtivo = null;
    }

    public function loadTendencias()
    {
        // Puxa as temperaturas do DB python (mariadb.lotolab_lotofacil_analytics.tendencias_dezenas)
        try {
            $records = DB::connection('mariadb')->select('SELECT dezena, temperatura FROM lotolab_lotofacil_analytics.tendencias_dezenas');
            $this->tendencias = [];
            foreach ($records as $r) {
                $this->tendencias[$r->dezena] = strtolower(trim($r->temperatura));
            }
        } catch (\Exception $e) {
            // Se falhar o DB de analytics, usa array vazio
            $this->tendencias = [];
        }
    }

    public $statusUsuarios = [];

    public function refreshBoard()
    {
        if (!$this->bolaoAtivo) return;

        $userId = auth()->id();
        
        // Pega os usuários vinculados neste bolão com o nome
        $pivotRecords = DB::table('lotofacil_bolao_user')
            ->join('users', 'lotofacil_bolao_user.user_id', '=', 'users.id')
            ->where('lotofacil_bolao_id', $this->bolaoAtivo->id)
            ->select('lotofacil_bolao_user.*', 'users.name as user_name')
            ->orderBy('lotofacil_bolao_user.id')
            ->get();

        $minhas = [];
        $outros = [];
        $statusArray = [];

        $usersIds = $pivotRecords->pluck('user_id')->toArray();
        $qtd = count($usersIds);
        $totalBolao = $this->bolaoAtivo->total_numeros;
        $porPessoa = $qtd > 0 ? floor($totalBolao / $qtd) : 0;
        $sobra = $qtd > 0 ? $totalBolao % $qtd : 0;
        $ultimoUser = end($usersIds);

        foreach ($pivotRecords as $record) {
            $selecionadas = json_decode($record->numeros_selecionados, true) ?? [];
            $meuLimite = ($record->user_id == $ultimoUser) ? ($porPessoa + $sobra) : $porPessoa;
            
            $statusArray[] = [
                'name' => $record->user_name,
                'selecionadas' => $selecionadas,
                'limite' => $meuLimite,
                'finalizado' => count($selecionadas) >= $meuLimite,
            ];

            if ($record->user_id == $userId) {
                $minhas = $selecionadas;
            } else {
                $outros = array_merge($outros, $selecionadas);
            }
        }

        $this->statusUsuarios = $statusArray;
        $this->minhasDezenas = $minhas;
        $this->dezenasOutros = array_unique($outros);
        
        $totalSelecionadas = count($this->minhasDezenas) + count($this->dezenasOutros);
        $this->isLocked = ($this->bolaoAtivo->status == 2) || ($totalSelecionadas >= $totalBolao);
        
        if ($totalSelecionadas >= $totalBolao && $this->bolaoAtivo->status != 2) {
            Bolao::where('id', $this->bolaoAtivo->id)->update(['status' => 2]);
            $this->bolaoAtivo->status = 2;
            $this->isLocked = true;
        }

        $this->calcularEstatisticas();
    }

    private function calcularEstatisticas()
    {
        // Combina todas as dezenas escolhidas até o momento
        $todas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));

        $stats = [
            'moldura' => 0,
            'miolo' => 0,
            'linhas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'colunas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        ];

        $molduraMap = [1,2,3,4,5,6,10,11,15,16,20,21,22,23,24,25];

        foreach ($todas as $num) {
            // Moldura / Miolo
            if (in_array($num, $molduraMap)) {
                $stats['moldura']++;
            } else {
                $stats['miolo']++;
            }

            // Linha (1 a 5)
            $linha = ceil($num / 5);
            $stats['linhas'][$linha]++;

            // Coluna (1 a 5)
            $coluna = $num % 5;
            if ($coluna === 0) $coluna = 5;
            $stats['colunas'][$coluna]++;
        }

        $this->stats = $stats;
    }

    public function toggleDezena($num)
    {
        \Log::info("Usuário clicou para alternar a dezena: " . $num);
        if (!$this->bolaoAtivo) {
            \Log::info("Nenhum bolaoAtivo!");
            return;
        }

        if ($this->isLocked) {
            \Log::info("Bolão bloqueado!");
            Notification::make()->title('Bolão encerrado! Não é possível alterar dezenas.')->danger()->send();
            return;
        }

        $userId = auth()->id();

        // 1. Verificar se é de outro usuário
        if (in_array($num, $this->dezenasOutros)) {
            Notification::make()->title('Esta dezena já foi escolhida por outro participante!')->danger()->send();
            return;
        }

        // Se eu já escolhi, eu posso desmarcar
        if (in_array($num, $this->minhasDezenas)) {
            $this->minhasDezenas = array_values(array_diff($this->minhasDezenas, [$num]));
            $this->saveMinhasDezenas();
            $this->sugestaoAtual = null; // Limpa sugestão se desmarcou
            $this->refreshBoard();
            return;
        }

        // Se estou marcando uma nova, valida Limite Pessoal
        // Descobre quantas dezenas eu posso marcar (se sou o último ou não)
        $usersIds = DB::table('lotofacil_bolao_user')->where('lotofacil_bolao_id', $this->bolaoAtivo->id)->orderBy('id')->pluck('user_id')->toArray();
        $isUltimo = end($usersIds) == $userId;
        
        $qtd = count($usersIds);
        $totalBolao = $this->bolaoAtivo->total_numeros;
        $porPessoa = floor($totalBolao / $qtd);
        $sobra = $totalBolao % $qtd;
        $meuLimite = $isUltimo ? ($porPessoa + $sobra) : $porPessoa;

        if (count($this->minhasDezenas) >= $meuLimite) {
            Notification::make()->title("Você já atingiu seu limite de {$meuLimite} dezenas!")->danger()->send();
            return;
        }

        // Valida Limites Globais (Simula a inserção)
        $tempStats = $this->simularEstatisticaAdicionando($num);
        
        if ($tempStats['moldura'] > $this->bolaoAtivo->max_moldura) {
            Notification::make()->title("Limite Global de Moldura ({$this->bolaoAtivo->max_moldura}) foi atingido!")->danger()->send();
            return;
        }
        if ($tempStats['miolo'] > $this->bolaoAtivo->max_miolo) {
            Notification::make()->title("Limite Global de Miolo ({$this->bolaoAtivo->max_miolo}) foi atingido!")->danger()->send();
            return;
        }
        foreach ($tempStats['linhas'] as $l => $count) {
            if ($count > $this->bolaoAtivo->max_linha) {
                Notification::make()->title("Limite Global de Linha ({$this->bolaoAtivo->max_linha}) atingido na linha {$l}!")->danger()->send();
                return;
            }
        }
        foreach ($tempStats['colunas'] as $c => $count) {
            if ($count > $this->bolaoAtivo->max_coluna) {
                Notification::make()->title("Limite Global de Coluna ({$this->bolaoAtivo->max_coluna}) atingido na coluna {$c}!")->danger()->send();
                return;
            }
        }

        // Tudo OK, salva!
        $this->minhasDezenas[] = $num;
        $this->saveMinhasDezenas();
        $this->buscarSugestaoCasada($num);
        $this->refreshBoard();
        $this->verificarEncerramento();
    }
    
    public function aceitarSugestao()
    {
        if ($this->sugestaoAtual && isset($this->sugestaoAtual['sugerida'])) {
            $dezena = $this->sugestaoAtual['sugerida'];
            $this->sugestaoAtual = null;
            $this->toggleDezena($dezena);
        }
    }

    private function buscarSugestaoCasada($num)
    {
        $this->sugestaoAtual = null;
        
        // Se já lotou a cota global ou pessoal, nem adianta sugerir
        if ($this->isLocked) return;
        
        $usersIds = DB::table('lotofacil_bolao_user')->where('lotofacil_bolao_id', $this->bolaoAtivo->id)->orderBy('id')->pluck('user_id')->toArray();
        $isUltimo = end($usersIds) == auth()->id();
        $qtd = count($usersIds);
        $totalBolao = $this->bolaoAtivo->total_numeros;
        $porPessoa = $qtd > 0 ? floor($totalBolao / $qtd) : 0;
        $sobra = $qtd > 0 ? $totalBolao % $qtd : 0;
        $meuLimite = $isUltimo ? ($porPessoa + $sobra) : $porPessoa;
        
        if (count($this->minhasDezenas) >= $meuLimite) return;

        try {
            $candidatas = DB::connection('mariadb')
                ->table('lotolab_lotofacil_analytics.lotofacil_estatisticas_duplas')
                ->where('dezena_1', $num)
                ->orderBy('frequencia', 'desc')
                ->limit(20)
                ->pluck('dezena_2');
                
            $todasMarcadas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));
            
            \Log::info("buscarSugestaoCasada para $num. Candidatas: " . json_encode($candidatas) . ". Todas marcadas: " . json_encode($todasMarcadas));
            
            foreach ($candidatas as $candidata) {
                // Se já estiver marcada, ignora
                if (in_array($candidata, $todasMarcadas)) continue;
                
                \Log::info("Encontrou sugestao forçada: $candidata");
                $this->sugestaoAtual = [
                    'marcada' => $num,
                    'sugerida' => $candidata
                ];
                break;
            }
        } catch (\Exception $e) {
            \Log::error("Erro buscarSugestaoCasada: " . $e->getMessage());
            // Ignora falhas de conexão de analytics
        }
    }

    private function verificarEncerramento()
    {
        if (!$this->bolaoAtivo) return;

        if ($this->bolaoAtivo->status == 1) {
            return; // Já encerrado
        }

        $todasMarcadas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros));
        $totalBolao = $this->bolaoAtivo->total_numeros;

        if (count($todasMarcadas) >= $totalBolao) {
            $this->bolaoAtivo->status = 1;
            $this->bolaoAtivo->save();
            
            // Gera os fechamentos
            try {
                $fechamentoService = new \App\Services\LotofacilFechamentoService();
                $fechamentoService->gerarFechamento($this->bolaoAtivo->id);
                Notification::make()->title('Bolão encerrado e Fechamento gerado com sucesso!')->success()->send();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erro ao gerar fechamento para o bolão {$this->bolaoAtivo->id}: " . $e->getMessage());
            }
        }
    }

    private function simularEstatisticaAdicionando($num)
    {
        $todas = array_unique(array_merge($this->minhasDezenas, $this->dezenasOutros, [$num]));

        $stats = [
            'moldura' => 0,
            'miolo' => 0,
            'linhas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'colunas' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        ];

        $molduraMap = [1,2,3,4,5,6,10,11,15,16,20,21,22,23,24,25];

        foreach ($todas as $n) {
            if (in_array($n, $molduraMap)) $stats['moldura']++;
            else $stats['miolo']++;

            $stats['linhas'][ceil($n / 5)]++;
            $coluna = $n % 5;
            if ($coluna === 0) $coluna = 5;
            $stats['colunas'][$coluna]++;
        }
        return $stats;
    }

    private function saveMinhasDezenas()
    {
        DB::table('lotofacil_bolao_user')
            ->where('lotofacil_bolao_id', $this->bolaoAtivo->id)
            ->where('user_id', auth()->id())
            ->update([
                'numeros_selecionados' => json_encode($this->minhasDezenas)
            ]);
    }
}

