<?php

namespace App\Services;

use App\Models\Lotofacil\Concurso;
use App\Models\Lotofacil\Combinacao18;

class ScoreLotofacilService
{
    // Top 5 dezenas mais frequentes (todas > 45.200 aparições)
    protected array $fixas = [10, 11, 20, 22, 25];
    
    // Top 5 dezenas menos frequentes (todas < 44.000 aparições)
    protected array $rejeitadas = [2, 3, 4, 5, 7];
    
    // Primos padrao
    protected array $primos = [2, 3, 5, 7, 11, 13, 17, 19, 23];
    
    // Fibonacci padrao
    protected array $fibonacci = [1, 2, 3, 5, 8, 13, 21];
    
    // Moldura padrao
    protected array $moldura = [1,2,3,4,5,6,10,11,15,16,20,21,22,23,24,25];

    public function calcularESalvar(Combinacao18 $combinacao)
    {
        $dezenasStr = str_replace('-', ',', $combinacao->dezenas);
        $dezenas = array_map('intval', explode(',', $dezenasStr));
        
        $score = $this->calcularScore($dezenas);
        
        $ultimos = Concurso::orderBy('concurso', 'desc')->limit(3)->get();
        
        $repetidas = [0, 0, 0];
        foreach ($ultimos as $index => $concurso) {
            $sorteadas = [
                $concurso->bola_1, $concurso->bola_2, $concurso->bola_3,
                $concurso->bola_4, $concurso->bola_5, $concurso->bola_6,
                $concurso->bola_7, $concurso->bola_8, $concurso->bola_9,
                $concurso->bola_10, $concurso->bola_11, $concurso->bola_12,
                $concurso->bola_13, $concurso->bola_14, $concurso->bola_15
            ];
            
            $sorteadas = array_map('intval', $sorteadas);
            $intersection = array_intersect($dezenas, $sorteadas);
            $repetidas[$index] = count($intersection);
            
            $rep = $repetidas[$index];
            if ($index === 0) {
                if ($rep == 11) $score += 15;
                elseif ($rep == 10 || $rep == 12) $score += 10;
            } else {
                if ($rep == 11) $score += 8;
                elseif ($rep == 10 || $rep == 12) $score += 5;
            }
        }
        
        $combinacao->update([
            'score' => $score,
            'repetidas_ant1' => $repetidas[0] ?? null,
            'repetidas_ant2' => $repetidas[1] ?? null,
            'repetidas_ant3' => $repetidas[2] ?? null,
        ]);
        
        return $score;
    }

    public function calcularScore(array $dezenas): int
    {
        $score = 0;
        
        // 1. Pares e Ímpares (Moda: 9x9)
        $pares = 0;
        foreach ($dezenas as $d) {
            if ($d % 2 == 0) $pares++;
        }
        $impares = 18 - $pares;
        if ($pares == 9 && $impares == 9) {
            $score += 20;
        } elseif (($pares == 8 && $impares == 10) || ($pares == 10 && $impares == 8)) {
            $score += 10;
        }
        
        // 2. Primos (Moda: 7, Média: 6.56)
        $qtdPrimos = count(array_intersect($dezenas, $this->primos));
        if ($qtdPrimos == 7) {
            $score += 15;
        } elseif ($qtdPrimos == 6) {
            $score += 10;
        }
        
        // 3. Fibonacci (Moda: 4)
        $qtdFibo = count(array_intersect($dezenas, $this->fibonacci));
        if ($qtdFibo == 4) {
            $score += 15;
        }
        
        // 4. Moldura (Moda: 11)
        $qtdMoldura = count(array_intersect($dezenas, $this->moldura));
        if ($qtdMoldura == 11) {
            $score += 15;
        } elseif ($qtdMoldura == 10 || $qtdMoldura == 12) {
            $score += 8;
        }
        
        // 5. Soma (Média 248 | Moda 247 | Tolerância 240 a 255)
        $soma = array_sum($dezenas);
        if ($soma >= 240 && $soma <= 255) {
            $score += 10;
        }
        
        $qtdFixas = count(array_intersect($dezenas, $this->fixas));
        $score += ($qtdFixas * 5);
        
        $qtdRejeitadas = count(array_intersect($dezenas, $this->rejeitadas));
        $score -= ($qtdRejeitadas * 5);
        
        return max(0, $score);
    }
}
