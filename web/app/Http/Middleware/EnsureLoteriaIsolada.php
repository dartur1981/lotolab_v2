<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoteriaIsolada
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $loteria ('megasena', 'lotofacil' ou 'quina')
     */
    public function handle(Request $request, Closure $next, string $loteria): Response
    {
        $current = session('pwa_current_loteria');

        // Se o usuário estiver tentando acessar uma loteria diferente da que está na sessão,
        // força o logout completo da loteria anterior
        if ($current && $current !== $loteria) {
            auth()->logout();
            session()->forget('pwa_bolao_ativo_id');
            session()->forget('pwa_megasena_bolao_ativo_id');
            session()->forget('pwa_quina_bolao_ativo_id');
            session()->forget('pwa_current_loteria');
            session()->invalidate();
            session()->regenerateToken();

            // Se estava acessando diretamente o login, permite prosseguir com a nova loteria
            if ($request->routeIs('*.login')) {
                session(['pwa_current_loteria' => $loteria]);
                return $next($request);
            }

            return redirect('/pwa');
        }

        // Define a loteria atual na sessão
        session(['pwa_current_loteria' => $loteria]);

        return $next($request);
    }
}
