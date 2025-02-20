<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestrictAccessByIP
{
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener la IP del usuario
        $ip = $request->header('X-Forwarded-For') ?? $request->ip();

        Log::info('Intento de acceso desde IP: ' . $ip);

        // Verificar si la IP está permitida en la tabla restrict_ips
        $isAllowed = DB::table('restrict_ips')->where('ip', $ip)->exists();

        if (!$isAllowed) {
            return response()->json([
                'message' => 'Acceso denegado: tu IP no está autorizada.'
            ], 403);
        }

        return $next($request);
    }
}
