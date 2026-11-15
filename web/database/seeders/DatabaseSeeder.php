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
        $lotofacil = Loteria::firstOrCreate(
            ['slug' => 'lotofacil'],
            [
                'nome' => 'Lotofácil',
                'total_de_numeros' => 25,
                'numeros_por_jogo' => 15,
                'cor_padrao' => '#9333ea',
                'ativo' => true,
            ]
        );

        $megasena = Loteria::firstOrCreate(
            ['slug' => 'megasena'],
            [
                'nome' => 'Mega-Sena',
                'total_de_numeros' => 60,
                'numeros_por_jogo' => 6,
                'cor_padrao' => '#209869',
                'ativo' => true,
            ]
        );

        foreach (User::all() as $user) {
            $user->loterias()->syncWithoutDetaching([$lotofacil->id, $megasena->id]);
        }
    }
}
