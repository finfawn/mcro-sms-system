<?php

namespace App\Services;

use App\Models\Service;
use App\Models\SmsTemplate;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(Service $service, string $event_key): void
    {
        $template = SmsTemplate::where('service_type', $service->service_type)
            ->where('event_key', $event_key)
            ->where('is_active', true)
            ->first();
        if (!$template) {
            return;
        }
        $body = $template->template_body;
        $body = str_replace('{{citizen_name}}', $service->citizen_name, $body);
        $body = str_replace('{{reference_no}}', $service->reference_no, $body);
        Log::info('SMS prepared', [
            'to' => $service->mobile_number,
            'message' => $body,
            'service_id' => $service->id,
            'event_key' => $event_key,
        ]);

        // Output to console for testing visibility
        $consoleMsg = "\n----------------------------------------\n" .
                      "SMS SIMULATION\n" .
                      "To: " . $service->mobile_number . "\n" .
                      "Message: " . $body . "\n" .
                      "----------------------------------------\n";
        file_put_contents('php://stderr', $consoleMsg);
    }

    
}
