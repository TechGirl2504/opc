<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Accepts permission names (any-of). Supports both:
     * - permission:view reports,export reports (Laravel-style params)
     * - permission:view reports|export reports (pipe-separated)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Unauthenticated',
                ],
            ], 401);
        }

        $normalized = collect($permissions)
            ->flatMap(fn ($p) => preg_split('/[|]/', $p) ?: [])
            ->map(fn ($p) => trim($p))
            ->filter()
            ->values()
            ->all();

        if (empty($normalized)) {
            return $next($request);
        }

        if (!$request->user()->hasAnyPermission($normalized)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this resource',
                ],
            ], 403);
        }

        return $next($request);
    }
}


