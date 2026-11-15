<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $user = auth()->user();

        // Se não estiver autenticado, redireciona para o login do Admin
        if (! $user) {
            return redirect()->guest(Filament::getLoginUrl() ?? '/admin/login');
        }

        // Se o usuário logado não tiver permissão para este painel,
        // força o logout da sessão anterior para evitar tela de 403 e redireciona para o login do Admin
        if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect('/admin/login');
        }

        return $next($request);
    }
}
