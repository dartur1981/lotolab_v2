<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Model;

class Combinacao18 extends Model
{
    protected $connection = 'analytics_lotofacil';
    protected $table = 'combinacoes_18';
    
    public $timestamps = false;
    
    protected $fillable = [
        'dezenas', 'pares', 'impares', 'primos', 'fibonacci', 
        'moldura', 'miolo', 'soma', 
        'score', 'repetidas_ant1', 'repetidas_ant2', 'repetidas_ant3',
        'acertos_15', 'acertos_14', 'acertos_13', 'acertos_12', 'acertos_11',
        'historico_15', 'historico_14', 'historico_13', 'historico_12', 'historico_11'
    ];
}
