<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Default authenticated landing page is Services index

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [ServiceController::class, 'dashboard'])->name('dashboard');
    Route::post('/dashboard/clear-sms', [ServiceController::class, 'clearSmsHistory'])->middleware('admin')->name('dashboard.clear-sms');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/bulk-upload', [ServiceController::class, 'bulkUploadForm'])->name('services.bulk-upload.form');
    Route::post('/services/bulk-upload', [ServiceController::class, 'bulkUploadStore'])->name('services.bulk-upload.store');
    Route::get('/services/bulk-upload/template', [ServiceController::class, 'bulkUploadTemplate'])->name('services.bulk-upload.template');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::put('/services/{service}/status', [ServiceController::class, 'updateStatus'])->name('services.update-status');
    Route::post('/services/bulk-status', [ServiceController::class, 'bulkStatus'])->name('services.bulk-status');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::post('/services/{id}/restore', [ServiceController::class, 'restore'])->name('services.restore');
    Route::post('/services/{id}/force-delete', [ServiceController::class, 'forceDelete'])->name('services.force-delete');
    Route::get('/sms-templates', [SmsTemplateController::class, 'index'])->name('sms-templates.index');
    Route::put('/sms-templates/{template}', [SmsTemplateController::class, 'update'])->middleware('admin')->name('sms-templates.update');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::post('/users/{id}/force-delete', [AdminUserController::class, 'forceDelete'])->name('users.force-delete');
    });
});

require __DIR__.'/auth.php';

// ClickSend Delivery Report webhook (public)
Route::match(['POST'], '/webhooks/clicksend', function (Request $request) {
    \Log::info('ClickSend Delivery Report', $request->all());
    return response()->json(['ok' => true]);
});
