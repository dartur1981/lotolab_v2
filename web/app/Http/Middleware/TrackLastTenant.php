<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            $tenantId = $tenant->getKey();

            // Salva na sessão
            session(['filament_last_tenant_id' => $tenantId]);

            // Salva no cookie por 30 dias
            cookie()->queue(cookie()->make('filament_last_tenant_id', (string) $tenantId, 60 * 24 * 30));

            // Se usuário autenticado, atualiza no banco se a coluna existir
            $user = auth()->user();
            if ($user && (!isset($user->last_loteria_id) || $user->last_loteria_id != $tenantId)) {
                try {
                    $user->forceFill(['last_loteria_id' => $tenantId])->saveQuietly();
                } catch (\Throwable $e) {
                    // Ignora silenciosamente se o campo ainda não existir no DB
                }
            }
        }

        return $next($request);
    }
}
