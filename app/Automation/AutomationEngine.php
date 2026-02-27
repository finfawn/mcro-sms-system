<?php

namespace App\Automation;

use App\Automation\Rules\TenDayPostingRule;
use App\Automation\Rules\MarriageLicenseReleaseRule;
use App\Models\Service;
use App\Services\SmsService;

class AutomationEngine
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Determine which rules apply to the service and execute status change logic.
     */
    public function handleStatusChange(Service $service): void
    {
        $rules = $this->getRulesForService($service);
        foreach ($rules as $rule) {
            $rule->handleStatusChange($service);
        }
    }

    /**
     * Determine which rules apply to the service and execute scheduled check logic.
     */
    public function handleScheduledCheck(Service $service): void
    {
        $rules = $this->getRulesForService($service);
        foreach ($rules as $rule) {
            $rule->handleScheduledCheck($service);
        }
    }

    /**
     * Get the list of rules applicable to the service type.
     * 
     * @return ServiceAutomationRule[]
     */
    protected function getRulesForService(Service $service): array
    {
        $rules = [];

        // Apply TenDayPostingRule to Delayed Registration types
        if (in_array($service->service_type, [
            'Delayed Registration',
            'Delayed Registration of Birth',
            'Delayed Registration of Death',
            'Delayed Registration of Marriage',
        ], true)) {
            $rules[] = new TenDayPostingRule($this->smsService);
        }

        if ($service->service_type === 'Application for Marriage License') {
            $rules[] = new MarriageLicenseReleaseRule($this->smsService);
        }

        return $rules;
    }
}
