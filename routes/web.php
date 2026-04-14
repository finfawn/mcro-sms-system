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
    Route::get('/scheduled', [ServiceController::class, 'scheduled'])->name('scheduled.index');
    

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/bulk-upload', [ServiceController::class, 'bulkUploadForm'])->name('services.bulk-upload.form');
    Route::post('/services/bulk-upload', [ServiceController::class, 'bulkUploadStore'])->name('services.bulk-upload.store');
    Route::get('/services/bulk-upload/template', [ServiceController::class, 'bulkUploadTemplate'])->name('services.bulk-upload.template');
    Route::get('/services/export', [ServiceController::class, 'export'])->name('services.export');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::put('/services/{service}/status', [ServiceController::class, 'updateStatus'])->name('services.update-status');
    Route::post('/services/{service}/scheduled-action', [ServiceController::class, 'runScheduledAction'])->name('services.scheduled-action');
    Route::post('/services/bulk-status', [ServiceController::class, 'bulkStatus'])->name('services.bulk-status');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->middleware('admin')->name('services.destroy');
    Route::post('/services/{id}/restore', [ServiceController::class, 'restore'])->middleware('admin')->name('services.restore');
    Route::post('/services/{id}/force-delete', [ServiceController::class, 'forceDelete'])->middleware('admin')->name('services.force-delete');
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

Route::match(['POST'], '/webhooks/clicksend', function (Request $request) {
    \Log::info('ClickSend Delivery Report', $request->all());
    return response()->json(['ok' => true]);
});

Route::post('/webhooks/textbee', function (Request $request) {
    $payload = $request->all();
    $to = (string)($payload['to'] ?? '');
    $status = strtolower((string)($payload['status'] ?? ''));
    $event = (string)($payload['event'] ?? ($payload['event_key'] ?? ''));
    $normalize = function(string $n): string {
        $t = trim($n);
        if ($t === '') return '';
        if (str_starts_with($t, '+')) return $t;
        $digits = preg_replace('/\D+/', '', $t);
        if (str_starts_with($digits, '63')) return '+'.$digits;
        if (str_starts_with($digits, '0')) return '+63'.substr($digits, 1);
        return '+'.$digits;
    };
    $toNorm = $normalize($to);
    $q = \App\Models\SmsMessage::query()
        ->where('provider', 'textbee')
        ->when($toNorm !== '', fn($qq) => $qq->where('to', $toNorm))
        ->when($event !== '', fn($qq) => $qq->where('event_key', $event))
        ->whereIn('status', ['dispatched','queued'])
        ->orderBy('created_at', 'desc');
    $msg = $q->first();
    if (!$msg) {
        \Log::warning('TextBee webhook: no matching queued message', [
            'to' => $toNorm ?: $to,
            'event' => $event,
            'payload' => $payload,
        ]);
        return response()->json(['ok' => true]);
    }
    if (in_array($status, ['sent','delivered','success'], true)) {
        $msg->status = 'sent';
        $msg->error = null;
    } elseif (in_array($status, ['failed','error'], true)) {
        $msg->status = 'failed';
        $msg->error = (string)($payload['error'] ?? 'failed');
    } else {
        $msg->status = 'sent';
    }
    $msg->save();
    return response()->json(['ok' => true]);
});
