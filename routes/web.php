<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SmsTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::put('/services/{service}/status', [ServiceController::class, 'updateStatus'])->name('services.update-status');
    Route::post('/services/bulk-status', [ServiceController::class, 'bulkStatus'])->name('services.bulk-status');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');
    Route::post('/services/{id}/restore', [ServiceController::class, 'restore'])->name('services.restore');
    Route::post('/services/{id}/force-delete', [ServiceController::class, 'forceDelete'])->name('services.force-delete');

    Route::get('/templates', [SmsTemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [SmsTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [SmsTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [SmsTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [SmsTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [SmsTemplateController::class, 'destroy'])->name('templates.destroy');
});

require __DIR__.'/auth.php';
