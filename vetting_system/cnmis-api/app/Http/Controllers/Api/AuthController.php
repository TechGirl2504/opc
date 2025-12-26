<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * User login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with('institution')->where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACCOUNT_INACTIVE',
                    'message' => 'Your account has been deactivated. Please contact administrator.'
                ]
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log login
        $this->auditService->logLogin($user);

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'institution' => $user->institution ? [
                        'id' => $user->institution->id,
                        'name' => $user->institution->name,
                        'code' => $user->institution->code,
                    ] : null,
                    'institution_id' => $user->institution_id,
                    'profile_picture' => $user->profile_picture,
                    'is_active' => $user->is_active,
                ],
                'token' => $token,
            ],
            'message' => 'Login successful'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'institution' => $user->institution ? [
                        'id' => $user->institution->id,
                        'name' => $user->institution->name,
                        'code' => $user->institution->code,
                    ] : null,
                    'institution_id' => $user->institution_id,
                    'profile_picture' => $user->profile_picture,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                ]
            ]
        ]);
    }

    /**
     * User logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        
        // Log logout
        $this->auditService->logLogout($user);
        
        // Delete current access token if it exists
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Refresh token (create new token)
     */
    public function refresh(Request $request)
    {
        // Delete current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $request->user()->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
            'message' => 'Token refreshed successfully'
        ]);
    }

    /**
     * Update password (authenticated user)
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->update([
                'password' => Hash::make($request->validated()['new_password']),
            ]);

            // Log password change
            $this->auditService->logPasswordChange($user);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PASSWORD_UPDATE_FAILED',
                    'message' => 'Failed to update password',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Request password reset (forgot password)
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link has been sent to your email',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESET_LINK_FAILED',
                    'message' => 'Unable to send password reset link',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to process password reset request',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'confirmed',
            ],
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();

                    // Log password change
                    $this->auditService->logPasswordChange($user);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PASSWORD_RESET_FAILED',
                    'message' => 'Invalid or expired reset token',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to reset password',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }
}
