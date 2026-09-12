<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Model;

class Concurso extends Model
{
    protected $connection = 'analytics_lotofacil';
    protected $table = 'resultados_lotofacil';
    protected $primaryKey = 'concurso';
    public $timestamps = false;
    
    protected $fillable = [
        'concurso',
        'data_sorteio',
        'bola_1','bola_2','bola_3','bola_4','bola_5',
        'bola_6','bola_7','bola_8','bola_9','bola_10',
        'bola_11','bola_12','bola_13','bola_14','bola_15'
    ];

    protected $casts = [
        'data_sorteio' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Concurso $concurso) {
            \Illuminate\Support\Facades\DB::connection('analytics_lotofacil')
                ->table('features_lotofacil')
                ->where('concurso', $concurso->concurso)
                ->delete();
        });
    }

    public function getDezenasAttribute(): array
    {
        $dezenas = [];
        for ($i = 1; $i <= 15; $i++) {
            $col = "bola_{$i}";
            if (!is_null($this->$col)) {
                $dezenas[] = sprintf('%02d', (int) $this->$col);
            }
        }
        return $dezenas;
    }
}
