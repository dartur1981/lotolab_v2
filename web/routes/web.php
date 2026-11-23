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

// Livewire PWA Quina
use App\Livewire\Pwa\Quina\Login as QuinaLogin;
use App\Livewire\Pwa\Quina\Dashboard as QuinaDashboard;
use App\Livewire\Pwa\Quina\Volante as QuinaVolante;
use App\Livewire\Pwa\Quina\Apostas as QuinaApostas;
use App\Livewire\Pwa\Quina\Resultado as QuinaResultado;
use App\Livewire\Pwa\Quina\Boloes as QuinaBoloes;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('/login', function () {
    return redirect('/app/login');
})->name('login');

// Hub Central PWA (Mobile) - Escolha de Loteria
Route::get('/pwa', function () {
    $boloesMega = \App\Models\Megasena\Bolao::whereIn('status', [0, 1])->count();
    $boloesLotofacil = \App\Models\Lotofacil\Bolao::whereIn('status', [0, 1])->count();
    $boloesQuina = \App\Models\Quina\Bolao::whereIn('status', [0, 1])->count();

    return view('pwa-hub', compact('boloesMega', 'boloesLotofacil', 'boloesQuina'));
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

// Rotas Isoladas do PWA Quina
Route::prefix('pwa/quina')->name('pwa.quina.')->middleware('loteria.isolada:quina')->group(function () {
    Route::get('/login', QuinaLogin::class)->name('login');
    Route::get('/boloes', QuinaBoloes::class)->name('boloes');
    Route::get('/', QuinaDashboard::class)->name('index');
    Route::get('/dashboard', QuinaDashboard::class)->name('dashboard');
    Route::get('/selecao', QuinaVolante::class)->name('selecao');
    Route::get('/apostas', QuinaApostas::class)->name('apostas');
    Route::get('/resultado', QuinaResultado::class)->name('resultado');

    // Logout obrigatório da Quina (redireciona para o Hub PWA)
    Route::get('/logout', function () {
        auth()->logout();
        session()->forget('pwa_quina_bolao_ativo_id');
        session()->forget('pwa_current_loteria');
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/pwa');
    })->name('logout');
});

// Redirecionamentos de compatibilidade para páginas do painel Filament por Tenant
Route::get('app/{tenant}/selecionar-numeros', function ($tenant) {
    $slug = match((string)$tenant) {
        '2', 'megasena' => 'megasena',
        '3', 'quina' => 'quina',
        default => 'lotofacil',
    };
    return redirect("/app/{$tenant}/{$slug}/selecionar-numeros");
});
Route::get('app/{tenant}/configuracoes', function ($tenant) {
    $slug = match((string)$tenant) {
        '2', 'megasena' => 'megasena',
        '3', 'quina' => 'quina',
        default => 'lotofacil',
    };
    return redirect("/app/{$tenant}/{$slug}/configuracoes");
});
Route::get('app/{tenant}/importar-resultados', function ($tenant) {
    $slug = match((string)$tenant) {
        '2', 'megasena' => 'megasena',
        '3', 'quina' => 'quina',
        default => 'lotofacil',
    };
    return redirect("/app/{$tenant}/{$slug}/importar-resultados");
});

// Rotas de Logout GET para conveniência
Route::get('admin/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/admin/login');
});
Route::get('app/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/app/login');
});
