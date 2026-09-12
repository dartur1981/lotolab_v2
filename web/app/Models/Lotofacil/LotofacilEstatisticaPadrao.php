<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotofacilEstatisticaPadrao extends Model
{
    use HasFactory;

    protected $table = 'lotolab_lotofacil_analytics_v2.lotofacil_estatisticas_padroes';

    protected $fillable = [
        'padrao',
        'valor',
        'atraso',
        'percentual',
    ];
}
