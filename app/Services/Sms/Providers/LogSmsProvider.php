<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProvider
{
    public function send(string $to, string $message): void
    {
        Log::info('SMS prepared', [
            'to' => $to,
            'message' => $message,
        ]);
        $consoleMsg = "\n----------------------------------------\n" .
                      "SMS SIMULATION\n" .
                      "To: " . $to . "\n" .
                      "Message: " . $message . "\n" .
                      "----------------------------------------\n";
        file_put_contents('php://stderr', $consoleMsg);
    }
}
