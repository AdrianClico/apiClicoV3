<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckClientToken
{
    public function handle(Request $request, Closure $next, string $clientSlug)
    {
        // 1. Ir al config a buscar el token esperado para este cliente
        $expectedToken = config("clients.{$clientSlug}.api_token");

        // 2. Si no hay token configurado en el sistema o no coincide con el enviado en los Headers
        if (!$expectedToken || $request->bearerToken() !== $expectedToken) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Acceso no autorizado. Token inválido o inexistente.'
            ], 401);
        }

        return $next($request);
    }
}
