<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\VettingController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DecisionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\Admin\InstitutionController;
use App\Http\Controllers\Api\Admin\ApplicationStatusController;
use App\Http\Controllers\Api\Admin\VettingTypeController;
use App\Http\Controllers\Api\Admin\DocumentTypeController;
use App\Http\Controllers\Api\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes (authentication)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Authentication routes
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/update-password', [AuthController::class, 'updatePassword']);

        // Application routes
        Route::get('/applications', [ApplicationController::class, 'index']);
        Route::post('/applications', [ApplicationController::class, 'store'])->middleware('permission:create applications');
        Route::get('/applications/{id}', [ApplicationController::class, 'show']);
        Route::put('/applications/{id}', [ApplicationController::class, 'update'])->middleware('auth:sanctum');
        Route::delete('/applications/{id}', [ApplicationController::class, 'destroy'])->middleware('permission:delete applications');
        Route::post('/applications/{id}/send-back-to-data-entry', [ApplicationController::class, 'sendBackToDataEntry'])->middleware('permission:manage application statuses');
        Route::post('/applications/{id}/assign-police', [ApplicationController::class, 'assignPolice'])->middleware('permission:assign applications');
        Route::post('/applications/{id}/assign-nis', [ApplicationController::class, 'assignNis'])->middleware('permission:assign applications');
        Route::post('/applications/{id}/forward-to-approval', [ApplicationController::class, 'forwardToApproval'])->middleware('permission:approve applications');
        Route::post('/applications/{id}/send-back-to-admin', [ApplicationController::class, 'sendBackToAdmin'])->middleware('permission:approve applications');
        Route::post('/applications/{id}/handle-approver-send-back', [ApplicationController::class, 'handleApproverSendBack'])->middleware('permission:edit applications');
        Route::get('/applications/{id}/status', [ApplicationController::class, 'statusHistory']);

        // Vetting routes
        Route::get('/applications/{id}/vetting/police', [VettingController::class, 'getPoliceVetting']);
        Route::post('/applications/{id}/vetting/police', [VettingController::class, 'submitPoliceVetting'])->middleware('permission:conduct police vetting'); // Keep for backward compatibility
        Route::post('/applications/{id}/vetting/police/draft', [VettingController::class, 'savePoliceVettingDraft'])->middleware('permission:conduct police vetting');
        Route::post('/applications/{id}/vetting/police/complete', [VettingController::class, 'completePoliceVetting'])->middleware('permission:conduct police vetting');
        Route::put('/applications/{id}/vetting/police', [VettingController::class, 'updatePoliceVetting'])->middleware('permission:conduct police vetting');
        Route::get('/applications/{id}/vetting/nis', [VettingController::class, 'getNisVetting']);
        Route::post('/applications/{id}/vetting/nis', [VettingController::class, 'submitNisVetting'])->middleware('permission:conduct nis vetting'); // Keep for backward compatibility
        Route::post('/applications/{id}/vetting/nis/draft', [VettingController::class, 'saveNisVettingDraft'])->middleware('permission:conduct nis vetting');
        Route::post('/applications/{id}/vetting/nis/complete', [VettingController::class, 'completeNisVetting'])->middleware('permission:conduct nis vetting');
        Route::put('/applications/{id}/vetting/nis', [VettingController::class, 'updateNisVetting'])->middleware('permission:conduct nis vetting');
        
        // Send back vetting (OPC only)
        Route::post('/vetting/{id}/send-back', [VettingController::class, 'sendBack'])->middleware('permission:send back vetting');

        // Document routes
        Route::get('/applications/{id}/documents', [DocumentController::class, 'index'])->middleware('permission:view documents');
        Route::post('/applications/{id}/documents', [DocumentController::class, 'store'])->middleware('permission:upload documents');
        Route::get('/documents/{id}', [DocumentController::class, 'show'])->middleware('permission:view documents');
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->middleware('permission:delete documents');
        Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->middleware('permission:download documents');
        Route::get('/documents/{id}/preview', [DocumentController::class, 'preview'])->middleware('permission:view documents');

        // Decision routes
        Route::post('/applications/{id}/approve', [DecisionController::class, 'approve'])->middleware('permission:approve applications');
        Route::post('/applications/{id}/deny', [DecisionController::class, 'deny'])->middleware('permission:deny applications');
        Route::get('/applications/{id}/decisions', [DecisionController::class, 'index']);

        // User list route (Admin only - needed for assignment)
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:view users');
        
        // User management routes (Admin only)
        Route::middleware('permission:manage users')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/users/{id}/activate', [UserController::class, 'activate']);
            Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
        });

        // Report routes
        // OPC only (plus Admin)
        Route::get('/reports/dashboard', [ReportController::class, 'dashboard'])->middleware('permission:view reports');
        Route::get('/reports/applications', [ReportController::class, 'applications'])->middleware('permission:view reports');
        Route::get('/reports/vetting', [ReportController::class, 'vetting'])->middleware('permission:view reports');
        Route::get('/reports/audit', [ReportController::class, 'audit'])->middleware('permission:view audit logs');
        Route::get('/reports/export', [ReportController::class, 'export'])->middleware('permission:export reports');

        // Notification routes
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Browser push subscriptions
        Route::get('/push-subscriptions', [PushSubscriptionController::class, 'index']);
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);

        // Admin Configuration Management Routes (Admin only)
        // Document Types - GET accessible to all authenticated users (needed for uploads)
        Route::get('/admin/document-types', [DocumentTypeController::class, 'index']);
        
        // Decision Values - GET accessible to all authenticated users (needed for vetting recommendations)
        Route::get('/admin/decision-values', [\App\Http\Controllers\Api\Admin\DecisionValueController::class, 'index']);

        Route::prefix('admin')->group(function () {
            // Institutions
            Route::apiResource('institutions', InstitutionController::class)->middleware('permission:manage institutions');

            // Application Statuses
            Route::apiResource('application-statuses', ApplicationStatusController::class)->middleware('permission:manage application statuses');

            // Vetting Types
            Route::apiResource('vetting-types', VettingTypeController::class)->middleware('permission:manage vetting types');

            // Document Types - CRUD operations (admin only)
            Route::post('/document-types', [DocumentTypeController::class, 'store'])->middleware('permission:manage document types');
            Route::get('/document-types/{id}', [DocumentTypeController::class, 'show'])->middleware('permission:manage document types');
            Route::put('/document-types/{id}', [DocumentTypeController::class, 'update'])->middleware('permission:manage document types');
            Route::delete('/document-types/{id}', [DocumentTypeController::class, 'destroy'])->middleware('permission:manage document types');

            // Roles & Permissions
            Route::apiResource('roles', RoleController::class)->middleware('permission:manage roles');
            Route::get('/roles/{id}/permissions', [RoleController::class, 'permissions'])->middleware('permission:manage roles');
            Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:manage permissions');
        });
    });
});
