<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstitution
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$institutions
     */
    public function handle(Request $request, Closure $next, string ...$institutions): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthenticated'
                ]
            ], 401);
        }

        if (!in_array($request->user()->institution, $institutions)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this resource from your institution'
                ]
            ], 403);
        }

        return $next($request);
    }
}
