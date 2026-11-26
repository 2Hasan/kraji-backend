<?php

use App\Http\Controllers\Admin\Web\ChangeRequestReviewController;
use App\Http\Controllers\Admin\Web\DashboardController;
use App\Http\Controllers\Admin\Web\LoginController;
use App\Http\Controllers\Admin\Web\OwnershipClaimReviewController;
use App\Http\Controllers\Admin\Web\PlateTemplateController;
use App\Http\Controllers\Admin\Web\StaffController;
use App\Http\Controllers\Admin\Web\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// لوحة الإدارة (ويب). كل الصفحات خلف جلسة حارس admin، والمراجع لا يدخل صفحات المشرف.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
    });

    Route::middleware('admin.session')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/requests', [ChangeRequestReviewController::class, 'index'])->name('requests.index');
        Route::get('/requests/{changeRequest}', [ChangeRequestReviewController::class, 'show'])->name('requests.show');
        Route::post('/requests/{changeRequest}/approve', [ChangeRequestReviewController::class, 'approve'])->name('requests.approve');
        Route::post('/requests/{changeRequest}/reject', [ChangeRequestReviewController::class, 'reject'])->name('requests.reject');

        Route::get('/claims', [OwnershipClaimReviewController::class, 'index'])->name('claims.index');
        Route::get('/claims/{claim}', [OwnershipClaimReviewController::class, 'show'])->name('claims.show');
        Route::get('/claims/{claim}/proof', [OwnershipClaimReviewController::class, 'proof'])->name('claims.proof');
        Route::post('/claims/{claim}/approve', [OwnershipClaimReviewController::class, 'approve'])->name('claims.approve');
        Route::post('/claims/{claim}/reject', [OwnershipClaimReviewController::class, 'reject'])->name('claims.reject');

        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');

        Route::get('/plate-templates', [PlateTemplateController::class, 'index'])->name('plate-templates.index');
        Route::post('/plate-templates', [PlateTemplateController::class, 'store'])->name('plate-templates.store');
        Route::post('/plate-templates/{template}/activate', [PlateTemplateController::class, 'activate'])->name('plate-templates.activate');

        // المشرف فقط
        Route::middleware('admin.session:admin')->group(function () {
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::post('/staff/{reviewer}/suspend', [StaffController::class, 'suspend'])->name('staff.suspend');
        });
    });
});
