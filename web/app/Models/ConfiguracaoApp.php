<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoApp extends Model
{
    use HasFactory;

    protected $table = 'lotolab_lotofacil_analytics_v2.configuracoes_app';
    protected $primaryKey = 'chave';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'chave',
        'valor',
    ];
}
