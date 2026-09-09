<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Model;

class EstrategiaFechamento extends Model
{
    protected $table = 'estrategia_fechamentos';

    protected $fillable = [
        'quantidade_jogos',
        'dezenas',
        'grupos',
    ];

    protected $casts = [
        'dezenas' => 'array',
        'grupos' => 'array',
    ];

    public function jogos()
    {
        return $this->hasMany(EstrategiaFechamentoJogo::class, 'estrategia_fechamento_id');
    }
}
