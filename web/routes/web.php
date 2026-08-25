<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Pwa\Lotofacil\Login as LotofacilLogin;
use App\Livewire\Pwa\Lotofacil\Dashboard as LotofacilDashboard;
use App\Livewire\Pwa\Lotofacil\Volante as LotofacilVolante;
use App\Livewire\Pwa\Lotofacil\Apostas as LotofacilApostas;
use App\Livewire\Pwa\Lotofacil\Resultado as LotofacilResultado;
use App\Livewire\Pwa\Lotofacil\Boloes as LotofacilBoloes;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/login', function () {
    return redirect('/app/login');
})->name('login');

// Redirecionador padrão para a Lotofácil PWA
Route::get('/pwa', function () {
    return redirect('/pwa/lotofacil/boloes');
});

// Rotas Isoladas do PWA Lotofácil
Route::prefix('pwa/lotofacil')->name('pwa.lotofacil.')->group(function () {
    Route::get('/login', LotofacilLogin::class)->name('login');
    Route::get('/boloes', LotofacilBoloes::class)->name('boloes');

    Route::get('/', LotofacilDashboard::class)->name('index');
    Route::get('/dashboard', LotofacilDashboard::class)->name('dashboard');
    Route::get('/selecao', LotofacilVolante::class)->name('selecao');
    Route::get('/apostas', LotofacilApostas::class)->name('apostas');
    Route::get('/resultado', LotofacilResultado::class)->name('resultado');

    Route::get('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/pwa/lotofacil/login');
    })->name('logout');
});
