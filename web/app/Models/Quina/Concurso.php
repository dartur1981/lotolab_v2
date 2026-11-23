<?php

namespace App\Models\Quina;

use Illuminate\Database\Eloquent\Model;

class Concurso extends Model
{
    protected $connection = 'analytics_quina';
    protected $table = 'resultados_quina';
    protected $primaryKey = 'concurso';
    public $timestamps = false;

    protected $fillable = [
        'concurso',
        'data_sorteio',
        'bola_1',
        'bola_2',
        'bola_3',
        'bola_4',
        'bola_5',
    ];

    protected $casts = [
        'data_sorteio' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Concurso $concurso) {
            \Illuminate\Support\Facades\DB::connection('analytics_quina')
                ->table('features_quina')
                ->where('concurso', $concurso->concurso)
                ->delete();
        });
    }

    public function getDezenasAttribute(): array
    {
        $dezenas = [];
        for ($i = 1; $i <= 5; $i++) {
            $col = "bola_{$i}";
            if (!is_null($this->$col)) {
                $dezenas[] = sprintf('%02d', (int) $this->$col);
            }
        }
        return $dezenas;
    }
}
