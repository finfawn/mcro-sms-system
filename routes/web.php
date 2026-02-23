<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SmsTemplateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Default authenticated landing page is Services index

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', function () {
        return redirect()->route('services.index');
    })->name('dashboard');

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

    Route::get('/sms-templates', [SmsTemplateController::class, 'index'])->name('sms-templates.index');
    Route::put('/sms-templates/{template}', [SmsTemplateController::class, 'update'])->name('sms-templates.update');
});

require __DIR__.'/auth.php';

// ClickSend Delivery Report webhook (public)
Route::match(['POST'], '/webhooks/clicksend', function (Request $request) {
    \Log::info('ClickSend Delivery Report', $request->all());
    return response()->json(['ok' => true]);
});
