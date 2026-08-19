<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Personal access token clients may be stateless. They retain the
        // legacy all-assigned-roles behavior because there is no session in
        // which to store an active role.
        if (!$request->hasSession()) {
            return $next($request);
        }

        // Authentication endpoints must remain available so the client can
        // read roles and select or change the active role.
        if ($request->is('api/v1/auth/*')) {
            return $next($request);
        }

        $roles = $user->roles()->pluck('name')->values()->all();
        $activeRole = $request->session()->get('active_role');

        if (!$activeRole || !in_array($activeRole, $roles, true)) {
            $request->session()->forget('active_role');

            if (count($roles) > 1) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ROLE_SELECTION_REQUIRED',
                        'message' => 'Please select an active role before continuing.',
                        'roles' => $roles,
                    ],
                ], 409);
            }

            if (count($roles) === 1) {
                $request->session()->put('active_role', $roles[0]);
            }
        }

        return $next($request);
    }
}
