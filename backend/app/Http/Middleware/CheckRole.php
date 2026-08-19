<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        // Support both middleware syntaxes:
        // - role:admin,opc_data_entry  (Laravel passes as multiple params)
        // - role:admin|opc_data_entry  (sometimes used with Spatie examples)
        $normalizedRoles = collect($roles)
            ->flatMap(fn ($r) => preg_split('/[|]/', $r) ?: [])
            ->map(fn ($r) => trim($r))
            ->filter()
            ->values()
            ->all();

        // Use Spatie Permission to check roles
        $hasRole = collect($normalizedRoles)->contains(
            fn (string $role) => $request->user()->hasActiveRole($role)
        );

        if (!$hasRole) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to access this resource'
                ]
            ], 403);
        }

        return $next($request);
    }
}
