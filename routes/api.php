<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PlateTemplateController as AdminPlateTemplateController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChangeRequestController;
use App\Http\Controllers\Api\DataExportController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\MyOwnershipClaimController;
use App\Http\Controllers\Api\NotificationSettingsController;
use App\Http\Controllers\Api\OdometerController;
use App\Http\Controllers\Api\OwnershipClaimController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PlateTemplateController;
use App\Http\Controllers\Api\ServiceRecordController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\VehicleChangeRequestController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'db' => DB::connection()->getDriverName(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
});

Route::middleware('auth:sanctum')->delete('/account', [AccountController::class, 'destroy']);
Route::middleware('auth:sanctum')->get('/account/export', [DataExportController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::get('/plate-templates', [PlateTemplateController::class, 'index']);
    Route::get('/plate-templates/{code}/svg', [PlateTemplateController::class, 'svg']);
    Route::get('/service-types', [ServiceTypeController::class, 'index']);
});

// السيارات: لا يوجد DELETE، الإجراء الوحيد أرشفة
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vehicles/lookup', [OwnershipClaimController::class, 'lookup']); // قبل {vehicle}
    Route::post('/vehicles/{vehicle}/ownership-claims', [OwnershipClaimController::class, 'store']);
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update']);
    Route::post('/vehicles/{vehicle}/archive', [VehicleController::class, 'archive']);
    Route::get('/vehicles/{vehicle}/schedules', [MaintenanceScheduleController::class, 'index']);
    Route::get('/vehicles/{vehicle}/change-requests', [VehicleChangeRequestController::class, 'index']);
    Route::get('/ownership-claims', [MyOwnershipClaimController::class, 'index']);
    Route::get('/me', fn (Request $request) => response()->json($request->user()->only(['id', 'name', 'email', 'locale'])));
    Route::get('/account/notification-settings', [NotificationSettingsController::class, 'show']);
    Route::patch('/account/notification-settings', [NotificationSettingsController::class, 'update']);
    Route::post('/vehicles/{vehicle}/odometer', [OdometerController::class, 'store']);
    Route::patch('/schedules/{schedule}', [MaintenanceScheduleController::class, 'update']);

    Route::get('/vehicles/{vehicle}/service-records', [ServiceRecordController::class, 'index']);
    Route::post('/vehicles/{vehicle}/service-records', [ServiceRecordController::class, 'store']);
    Route::get('/service-records/{record}', [ServiceRecordController::class, 'show']);
    Route::post('/service-records/{record}/attachments', [ServiceRecordController::class, 'storeAttachment']);
    Route::post('/service-records/{record}/change-requests', [ChangeRequestController::class, 'store']);
});

// لوحة الإدارة والمراجعة
Route::post('/admin/login', [AdminController::class, 'login']);

Route::prefix('admin')->middleware(['auth:sanctum', 'admin:admin'])->group(function () {
    Route::post('/reviewers', [AdminController::class, 'createReviewer']);
    Route::post('/reviewers/{reviewer}/suspend', [AdminController::class, 'suspendReviewer']);
    Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/requests', [ChangeRequestController::class, 'index']);
    Route::post('/requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve']);
    Route::post('/requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject']);
    Route::post('/plate-templates', [AdminPlateTemplateController::class, 'store']);
    Route::post('/plate-templates/{template}/activate', [AdminPlateTemplateController::class, 'activate']);
    Route::post('/claims/{claim}/approve', [OwnershipClaimController::class, 'approve']);
    Route::post('/claims/{claim}/reject', [OwnershipClaimController::class, 'reject']);
});

// الرابط الموقّع للمرفق: ما يحتاج توكن، والصلاحية 15 دقيقة
Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
