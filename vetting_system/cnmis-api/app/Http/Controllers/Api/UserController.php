<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with(['institution', 'roles']);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->has('role')) {
                $roleName = $request->get('role');
                // Use Spatie Permission's role scope
                $query->role($roleName);
            }

            // Filter by institution
            if ($request->has('institution_id')) {
                $query->where('institution_id', $request->get('institution_id'));
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $isActive = $request->get('is_active');
                // Convert string 'true'/'false' to boolean
                if (is_string($isActive)) {
                    $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
                }
                $query->where('is_active', $isActive);
            }

            // Order by (validate column exists)
            $orderBy = $request->get('order_by', 'created_at');
            $orderDir = $request->get('order_dir', 'desc');
            // Only allow valid columns for ordering
            $allowedOrderColumns = ['id', 'username', 'email', 'created_at', 'updated_at'];
            if (in_array($orderBy, $allowedOrderColumns)) {
                $query->orderBy($orderBy, $orderDir);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $users = $query->paginate($perPage);

            // Convert items to array - items() returns a collection, but we need to ensure it's mapped correctly
            $usersData = collect($users->items())->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'institution' => $user->institution ? [
                        'id' => $user->institution->id,
                        'name' => $user->institution->name,
                        'code' => $user->institution->code,
                    ] : null,
                    'institution_id' => $user->institution_id,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $usersData,
                ],
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve users: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve users: ' . $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $roleName = $validated['role'];

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => Hash::make($validated['password']),
                'institution_id' => $validated['institution_id'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Assign role
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }

            // Log audit
            $this->auditService->logCreate($request->user(), User::class, $user->id, $user->toArray());

            return response()->json([
                'success' => true,
                'data' => $user->load(['institution', 'roles']),
                'message' => 'User created successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CREATION_FAILED',
                    'message' => 'Failed to create user: ' . $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = User::with(['institution', 'roles', 'permissions'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'User not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve user',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $oldValues = $user->toArray();
            $validated = $request->validated();

            // Update password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Remove role from validated (handle separately)
            $roleName = $validated['role'] ?? null;
            unset($validated['role']);

            $user->update($validated);

            // Update role if provided
            if ($roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $user->syncRoles([$role]);
                }
            }

            // Log audit
            $this->auditService->logUpdate($request->user(), User::class, $user->id, $oldValues, $user->fresh()->toArray());

            return response()->json([
                'success' => true,
                'data' => $user->fresh(['institution', 'roles']),
                'message' => 'User updated successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'User not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UPDATE_FAILED',
                    'message' => 'Failed to update user: ' . $e->getMessage(),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Remove the specified user (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $oldValues = $user->toArray();

            // Prevent self-deletion
            if ($user->id === request()->user()->id) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_OPERATION',
                        'message' => 'You cannot delete your own account',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 400);
            }

            $user->delete();

            // Log audit
            $this->auditService->logDelete(request()->user(), User::class, $user->id, $oldValues);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'User not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_FAILED',
                    'message' => 'Failed to delete user',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Activate user account
     */
    public function activate(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $oldValues = $user->toArray();

            $user->update(['is_active' => true]);

            // Log audit
            $this->auditService->logUpdate(request()->user(), User::class, $user->id, $oldValues, $user->fresh()->toArray());

            return response()->json([
                'success' => true,
                'data' => $user->fresh(['institution', 'roles']),
                'message' => 'User activated successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'User not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ACTIVATION_FAILED',
                    'message' => 'Failed to activate user',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Deactivate user account
     */
    public function deactivate(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $oldValues = $user->toArray();

            // Prevent self-deactivation
            if ($user->id === request()->user()->id) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_OPERATION',
                        'message' => 'You cannot deactivate your own account',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 400);
            }

            $user->update(['is_active' => false]);

            // Log audit
            $this->auditService->logUpdate(request()->user(), User::class, $user->id, $oldValues, $user->fresh()->toArray());

            return response()->json([
                'success' => true,
                'data' => $user->fresh(['institution', 'roles']),
                'message' => 'User deactivated successfully',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'User not found',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DEACTIVATION_FAILED',
                    'message' => 'Failed to deactivate user',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }
}
