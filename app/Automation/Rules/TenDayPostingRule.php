<?php

namespace App\Automation\Rules;

use App\Automation\ServiceAutomationRule;
use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TenDayPostingRule implements ServiceAutomationRule
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handleStatusChange(Service $service): void
    {
        // If status changed to "Posted" AND sms_posting_sent == false
        if ($service->status === 'Posted' && !$service->sms_posting_sent) {
            
            // Set posting_start_date = today
            $service->posting_start_date = Carbon::today();
            
            // Mark sms_posting_sent = true
            $service->sms_posting_sent = true;
            $service->save();

            Log::info("TenDayPostingRule: Posting started for Service #{$service->id} (SMS merged into verification_consistent)");
        }
    }

    public function handleScheduledCheck(Service $service): void
    {
        // If status == "Posted"
        if ($service->status !== 'Posted') {
            return;
        }

        // AND posting_start_date IS NOT NULL
        if (!$service->posting_start_date) {
            return;
        }

        // AND posting_start_date <= today - 10 days
        // 10 days posting means it is ready on the 11th day? Or after 10 days passed?
        // Usually "10-day posting period" means it is posted for 10 days.
        // So on day 11 it is ready.
        // The prompt says: "posting_start_date <= today - 10 days"
        // Example: Posted on Jan 1. Today is Jan 11. 11 - 10 = 1. Jan 1 <= Jan 1. True.
        // So on the 11th day (or after 10 full days), it triggers.
        $thresholdDate = Carbon::today()->subDays(10);
        
        if ($service->posting_start_date <= $thresholdDate) {
            
            // AND sms_ready_sent == false
            if (!$service->sms_ready_sent) {
                
                // Change status to "Ready for Release"
                $service->status = 'Ready for Release';
                
                // Mark sms_ready_sent = true
                $service->sms_ready_sent = true;
                $service->save();

                // Send SMS using event_key = "ready_for_release"
                $this->smsService->send($service, 'ready_for_release');
                
                ServiceStatusLog::create([
                    'service_id' => $service->id,
                    'status' => 'Ready for Release',
                    'note' => null,
                ]);
                Log::info("TenDayPostingRule: Ready for release processed for Service #{$service->id}");
            }
        }
    }
}
