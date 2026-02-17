<?php

namespace App\Automation;

use App\Models\Service;

interface ServiceAutomationRule
{
    /**
     * Handle actions triggered by status changes.
     */
    public function handleStatusChange(Service $service): void;

    /**
     * Handle actions triggered by scheduled checks (e.g. date-based).
     */
    public function handleScheduledCheck(Service $service): void;
}
