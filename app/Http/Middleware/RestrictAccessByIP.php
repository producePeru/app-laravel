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
        $ip = $request->header('X-Forwarded-For') ?? $request->ip();

        Log::info('Intento de acceso desde IP: ' . $ip);

        $isAllowed = DB::table('restrict_ips')
            ->where('ip', $ip)
            ->where('access', 'eventos')
            ->exists();

        if (!$isAllowed) {
            return response()->json([
                'message' => 'Acceso denegado.'
            ], 403);
        }

        return $next($request);
    }
}
