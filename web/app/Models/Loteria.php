<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Models\Contracts\HasName;

class Loteria extends Model implements HasName
{
    protected $fillable = [
        'nome',
        'slug',
        'total_de_numeros',
        'numeros_por_jogo',
        'cor_padrao',
        'ativo',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function getFilamentName(): string
    {
        return $this->nome;
    }
}
