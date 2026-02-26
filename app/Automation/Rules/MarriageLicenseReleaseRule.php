<?php

namespace App\Automation\Rules;

use App\Automation\ServiceAutomationRule;
use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Services\SmsService;
use Carbon\Carbon;

class MarriageLicenseReleaseRule implements ServiceAutomationRule
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function handleStatusChange(Service $service): void
    {
        if ($service->service_type !== 'Application for Marriage License') {
            return;
        }
        if ($service->status === 'Posted' && !$service->posting_start_date) {
            $service->posting_start_date = Carbon::today();
            $service->save();
        }
    }

    public function handleScheduledCheck(Service $service): void
    {
        if ($service->service_type !== 'Application for Marriage License') {
            return;
        }
        if ($service->status !== 'Posted') {
            return;
        }
        if (!$service->posting_start_date) {
            return;
        }
        $threshold = Carbon::today()->subWeekdays(10);
        if ($service->posting_start_date <= $threshold && !$service->sms_release_sent) {
            $service->status = 'Released';
            $service->release_date = Carbon::today();
            $service->sms_release_sent = true;
            $service->save();
            $this->smsService->send($service, 'releasing');
            ServiceStatusLog::create([
                'service_id' => $service->id,
                'status' => 'Released',
                'note' => null,
            ]);
        }
    }
}
