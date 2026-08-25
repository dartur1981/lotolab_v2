<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotofacilFechamento extends Model
{
    use HasFactory;

    protected $table = 'lotofacil_fechamentos';

    protected $fillable = [
        'bolao_id',
        'quantidade_jogos',
        'dezenas',
        'resultado',
        'grupos',
    ];

    protected $casts = [
        'dezenas' => 'array',
        'resultado' => 'array',
        'grupos' => 'array',
    ];

    public function bolao(): BelongsTo
    {
        return $this->belongsTo(Bolao::class, 'bolao_id');
    }

    public function jogos(): HasMany
    {
        return $this->hasMany(LotofacilFechamentoJogo::class, 'fechamento_id');
    }

    protected static function booted()
    {
        static::saved(function ($fechamento) {
            if (!empty($fechamento->resultado)) {
                
                // Extrair array de resultado
                $resultado = $fechamento->resultado;
                if (is_string($resultado)) {
                    $resultado = json_decode($resultado, true) ?? [];
                }
                
                // Se for um array válido de números
                if (is_array($resultado) && count($resultado) > 0) {
                    $resultadoInt = array_map('intval', $resultado);
                    
                    // Calcular e atualizar os acertos de todos os jogos deste fechamento
                    foreach ($fechamento->jogos as $jogo) {
                        $dezenas = $jogo->dezenas;
                        if (is_string($dezenas)) {
                            $dezenas = json_decode($dezenas, true) ?? [];
                        }
                        if (is_array($dezenas)) {
                            $dezenasInt = array_map('intval', $dezenas);
                            $acertos = count(array_intersect($dezenasInt, $resultadoInt));
                            $jogo->update(['acertos' => $acertos]);
                        }
                    }
                }

                $bolao = $fechamento->bolao;
                if ($bolao && $bolao->status !== 4) {
                    $bolao->update(['status' => 4]);
                }
            }
        });
    }
}
