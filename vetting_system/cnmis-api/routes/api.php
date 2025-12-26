<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\VettingController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DecisionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\InstitutionController;
use App\Http\Controllers\Api\Admin\ApplicationStatusController;
use App\Http\Controllers\Api\Admin\VettingTypeController;
use App\Http\Controllers\Api\Admin\DocumentTypeController;
use App\Http\Controllers\Api\Admin\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes (authentication)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Authentication routes
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::post('/auth/update-password', [AuthController::class, 'updatePassword']);

        // Application routes
        Route::get('/applications', [ApplicationController::class, 'index']);
        Route::post('/applications', [ApplicationController::class, 'store'])->middleware('role:admin,opc_data_entry');
        Route::get('/applications/{id}', [ApplicationController::class, 'show']);
        Route::put('/applications/{id}', [ApplicationController::class, 'update'])->middleware('role:admin,opc_data_entry');
        Route::delete('/applications/{id}', [ApplicationController::class, 'destroy'])->middleware('role:admin');
        Route::post('/applications/{id}/assign-police', [ApplicationController::class, 'assignPolice'])->middleware('role:admin');
        Route::post('/applications/{id}/assign-nis', [ApplicationController::class, 'assignNis'])->middleware('role:admin');
        Route::post('/applications/{id}/forward-to-approval', [ApplicationController::class, 'forwardToApproval'])->middleware('role:admin,opc_approver');
        Route::post('/applications/{id}/send-back-to-admin', [ApplicationController::class, 'sendBackToAdmin'])->middleware('role:opc_approver');
        Route::post('/applications/{id}/handle-approver-send-back', [ApplicationController::class, 'handleApproverSendBack'])->middleware('role:admin');
        Route::get('/applications/{id}/status', [ApplicationController::class, 'statusHistory']);

        // Vetting routes
        Route::get('/applications/{id}/vetting/police', [VettingController::class, 'getPoliceVetting']);
        Route::post('/applications/{id}/vetting/police', [VettingController::class, 'submitPoliceVetting'])->middleware('role:police_officer'); // Keep for backward compatibility
        Route::post('/applications/{id}/vetting/police/draft', [VettingController::class, 'savePoliceVettingDraft'])->middleware('role:police_officer');
        Route::post('/applications/{id}/vetting/police/complete', [VettingController::class, 'completePoliceVetting'])->middleware('role:police_officer');
        Route::put('/applications/{id}/vetting/police', [VettingController::class, 'updatePoliceVetting'])->middleware('role:police_officer');
        Route::get('/applications/{id}/vetting/nis', [VettingController::class, 'getNisVetting']);
        Route::post('/applications/{id}/vetting/nis', [VettingController::class, 'submitNisVetting'])->middleware('role:nis_officer'); // Keep for backward compatibility
        Route::post('/applications/{id}/vetting/nis/draft', [VettingController::class, 'saveNisVettingDraft'])->middleware('role:nis_officer');
        Route::post('/applications/{id}/vetting/nis/complete', [VettingController::class, 'completeNisVetting'])->middleware('role:nis_officer');
        Route::put('/applications/{id}/vetting/nis', [VettingController::class, 'updateNisVetting'])->middleware('role:nis_officer');
        
        // Send back vetting (OPC only)
        Route::post('/vetting/{id}/send-back', [VettingController::class, 'sendBack'])->middleware('role:opc_approver,admin');

        // Document routes
        Route::get('/applications/{id}/documents', [DocumentController::class, 'index']);
        Route::post('/applications/{id}/documents', [DocumentController::class, 'store']);
        Route::get('/documents/{id}', [DocumentController::class, 'show']);
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
        Route::get('/documents/{id}/download', [DocumentController::class, 'download']);
        Route::get('/documents/{id}/preview', [DocumentController::class, 'preview']);

        // Decision routes
        Route::post('/applications/{id}/approve', [DecisionController::class, 'approve'])->middleware('role:opc_approver');
        Route::post('/applications/{id}/deny', [DecisionController::class, 'deny'])->middleware('role:opc_approver');
        Route::get('/applications/{id}/decisions', [DecisionController::class, 'index']);

        // User list route (Admin only - needed for assignment)
        Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
        
        // User management routes (Admin only)
        Route::middleware('role:admin')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/users/{id}/activate', [UserController::class, 'activate']);
            Route::post('/users/{id}/deactivate', [UserController::class, 'deactivate']);
        });

        // Report routes
        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/reports/applications', [ReportController::class, 'applications']);
        Route::get('/reports/vetting', [ReportController::class, 'vetting']);
        Route::get('/reports/audit', [ReportController::class, 'audit'])->middleware('role:admin');
        Route::get('/reports/export', [ReportController::class, 'export']);

        // Notification routes
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Admin Configuration Management Routes (Admin only)
        // Document Types - GET accessible to all authenticated users (needed for uploads)
        Route::get('/admin/document-types', [DocumentTypeController::class, 'index']);
        
        // Decision Values - GET accessible to all authenticated users (needed for vetting recommendations)
        Route::get('/admin/decision-values', [\App\Http\Controllers\Api\Admin\DecisionValueController::class, 'index']);

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Institutions
            Route::apiResource('institutions', InstitutionController::class);

            // Application Statuses
            Route::apiResource('application-statuses', ApplicationStatusController::class);

            // Vetting Types
            Route::apiResource('vetting-types', VettingTypeController::class);

            // Document Types - CRUD operations (admin only)
            Route::post('/document-types', [DocumentTypeController::class, 'store']);
            Route::get('/document-types/{id}', [DocumentTypeController::class, 'show']);
            Route::put('/document-types/{id}', [DocumentTypeController::class, 'update']);
            Route::delete('/document-types/{id}', [DocumentTypeController::class, 'destroy']);

            // Roles & Permissions
            Route::apiResource('roles', RoleController::class);
            Route::get('/roles/{id}/permissions', [RoleController::class, 'permissions']);
            Route::get('/permissions', [RoleController::class, 'permissions']);
        });
    });
});
