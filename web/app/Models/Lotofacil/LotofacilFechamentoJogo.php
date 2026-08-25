<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotofacilFechamentoJogo extends Model
{
    use HasFactory;

    protected $table = 'lotofacil_fechamento_jogos';

    protected $fillable = [
        'fechamento_id',
        'dezenas',
        'status',
        'acertos',
    ];

    protected $casts = [
        'dezenas' => 'array',
    ];

    public function fechamento(): BelongsTo
    {
        return $this->belongsTo(LotofacilFechamento::class, 'fechamento_id');
    }

    protected static function booted()
    {
        static::saved(function ($jogo) {
            $fechamento = $jogo->fechamento;
            if ($fechamento && $fechamento->bolao_id) {
                $totalJogos = $fechamento->jogos()->count();
                $jogosProntos = $fechamento->jogos()->whereIn('status', ['3', '4'])->count();
                
                if ($totalJogos > 0 && $totalJogos === $jogosProntos) {
                    $bolao = $fechamento->bolao;
                    // Atualiza para 'Apostado' (3) se ainda não for Apostado ou Apurado
                    if ($bolao && $bolao->status !== 3 && $bolao->status !== 4) {
                        $bolao->update(['status' => 3]);
                    }
                }
            }
        });
    }
}
