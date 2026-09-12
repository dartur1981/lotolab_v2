<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotofacilEstatisticaTrinca extends Model
{
    use HasFactory;

    protected $table = 'lotolab_lotofacil_analytics_v2.lotofacil_estatisticas_trincas';

    protected $fillable = [
        'dezenas',
        'frequencia',
        'percentual',
    ];
}
