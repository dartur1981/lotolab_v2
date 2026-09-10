<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Loteria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $loteria = Loteria::firstOrCreate(
            ['slug' => 'lotofacil'],
            [
                'nome' => 'Lotofácil',
                'total_de_numeros' => 25,
                'numeros_por_jogo' => 15,
                'cor_padrao' => '#9333ea',
                'ativo' => true,
            ]
        );

        foreach (User::all() as $user) {
            $user->loterias()->syncWithoutDetaching([$loteria->id]);
        }
    }
}
