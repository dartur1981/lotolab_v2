<?php

use Illuminate\Support\Facades\Route;

// Livewire PWA Lotofácil
use App\Livewire\Pwa\Lotofacil\Login as LotofacilLogin;
use App\Livewire\Pwa\Lotofacil\Dashboard as LotofacilDashboard;
use App\Livewire\Pwa\Lotofacil\Volante as LotofacilVolante;
use App\Livewire\Pwa\Lotofacil\Apostas as LotofacilApostas;
use App\Livewire\Pwa\Lotofacil\Resultado as LotofacilResultado;
use App\Livewire\Pwa\Lotofacil\Boloes as LotofacilBoloes;

// Livewire PWA Mega-Sena
use App\Livewire\Pwa\Megasena\Login as MegasenaLogin;
use App\Livewire\Pwa\Megasena\Dashboard as MegasenaDashboard;
use App\Livewire\Pwa\Megasena\Volante as MegasenaVolante;
use App\Livewire\Pwa\Megasena\Apostas as MegasenaApostas;
use App\Livewire\Pwa\Megasena\Resultado as MegasenaResultado;
use App\Livewire\Pwa\Megasena\Boloes as MegasenaBoloes;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/login', function () {
    return redirect('/app/login');
})->name('login');

// Hub Central PWA (Mobile) - Escolha de Loteria
Route::get('/pwa', function () {
    return view('pwa-hub');
});

// Rotas Isoladas do PWA Lotofácil
Route::prefix('pwa/lotofacil')->name('pwa.lotofacil.')->middleware('loteria.isolada:lotofacil')->group(function () {
    Route::get('/login', LotofacilLogin::class)->name('login');
    Route::get('/boloes', LotofacilBoloes::class)->name('boloes');
    Route::get('/', LotofacilDashboard::class)->name('index');
    Route::get('/dashboard', LotofacilDashboard::class)->name('dashboard');
    Route::get('/selecao', LotofacilVolante::class)->name('selecao');
    Route::get('/apostas', LotofacilApostas::class)->name('apostas');
    Route::get('/resultado', LotofacilResultado::class)->name('resultado');

    // Logout obrigatório da Lotofácil (redireciona para o Hub PWA)
    Route::get('/logout', function () {
        auth()->logout();
        session()->forget('pwa_bolao_ativo_id');
        session()->forget('pwa_current_loteria');
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/pwa');
    })->name('logout');
});

// Rotas Isoladas do PWA Mega-Sena
Route::prefix('pwa/megasena')->name('pwa.megasena.')->middleware('loteria.isolada:megasena')->group(function () {
    Route::get('/login', MegasenaLogin::class)->name('login');
    Route::get('/boloes', MegasenaBoloes::class)->name('boloes');
    Route::get('/', MegasenaDashboard::class)->name('index');
    Route::get('/dashboard', MegasenaDashboard::class)->name('dashboard');
    Route::get('/selecao', MegasenaVolante::class)->name('selecao');
    Route::get('/apostas', MegasenaApostas::class)->name('apostas');
    Route::get('/resultado', MegasenaResultado::class)->name('resultado');

    // Logout obrigatório da Mega-Sena (redireciona para o Hub PWA)
    Route::get('/logout', function () {
        auth()->logout();
        session()->forget('pwa_megasena_bolao_ativo_id');
        session()->forget('pwa_current_loteria');
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/pwa');
    })->name('logout');
});
