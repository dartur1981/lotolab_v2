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

// Hub Central PWA (Mobile)
Route::get('/pwa', function () {
    if (auth()->check()) {
        $userId = auth()->id();
        $hasMega = \App\Models\Megasena\Bolao::whereHas('users', fn($q) => $q->where('users.id', $userId))->whereIn('status', [0, 1, 2])->exists();
        $hasLoto = \App\Models\Lotofacil\Bolao::whereHas('users', fn($q) => $q->where('users.id', $userId))->whereIn('status', [0, 1, 2])->exists();

        if ($hasMega && !$hasLoto) {
            return redirect('/pwa/megasena/dashboard');
        } elseif ($hasLoto && !$hasMega) {
            return redirect('/pwa/lotofacil/dashboard');
        }
    }

    return view('pwa-hub');
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

// Rotas Isoladas do PWA Mega-Sena
Route::prefix('pwa/megasena')->name('pwa.megasena.')->group(function () {
    Route::get('/login', MegasenaLogin::class)->name('login');
    Route::get('/boloes', MegasenaBoloes::class)->name('boloes');

    Route::get('/', MegasenaDashboard::class)->name('index');
    Route::get('/dashboard', MegasenaDashboard::class)->name('dashboard');
    Route::get('/selecao', MegasenaVolante::class)->name('selecao');
    Route::get('/apostas', MegasenaApostas::class)->name('apostas');
    Route::get('/resultado', MegasenaResultado::class)->name('resultado');

    Route::get('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/pwa/megasena/login');
    })->name('logout');
});
