<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProvider
{
    public function send(string $to, string $message): void
    {
        Log::info('Log SMS provider accepted message', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
