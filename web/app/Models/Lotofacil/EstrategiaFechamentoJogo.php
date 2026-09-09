<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Model;

class EstrategiaFechamentoJogo extends Model
{
    protected $table = 'estrategia_fechamento_jogos';

    protected $fillable = [
        'estrategia_fechamento_id',
        'dezenas',
        'score',
        'status',
    ];

    protected $casts = [
        'dezenas' => 'array',
    ];

    public function fechamento()
    {
        return $this->belongsTo(EstrategiaFechamento::class, 'estrategia_fechamento_id');
    }
}
