<?php

namespace App\Models\Quina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuinaFechamentoJogo extends Model
{
    use HasFactory;

    protected $table = 'quina_fechamento_jogos';

    protected $fillable = [
        'fechamento_id',
        'dezenas',
        'grupos',
        'status',
        'acertos',
    ];

    protected $casts = [
        'dezenas' => 'array',
    ];

    public function fechamento(): BelongsTo
    {
        return $this->belongsTo(QuinaFechamento::class, 'fechamento_id');
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
                    if ($bolao && $bolao->status !== 3 && $bolao->status !== 4) {
                        $bolao->update(['status' => 3]);
                    }
                }
            }
        });
    }
}
