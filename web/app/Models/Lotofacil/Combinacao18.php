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
        'acertos_15', 'acertos_14', 'acertos_13', 'acertos_12', 'acertos_11'
    ];
}
