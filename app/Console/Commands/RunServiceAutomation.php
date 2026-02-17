<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Automation\AutomationEngine;
use Illuminate\Support\Facades\Log;

class RunServiceAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:run-automation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled automation checks for services';

    /**
     * Execute the console command.
     */
    public function handle(AutomationEngine $automation)
    {
        $this->info('Starting service automation check...');

        // Fetch active services that might need automation
        // For efficiency, we could filter here, but AutomationEngine handles per-service logic.
        // Let's grab all active services (e.g. not released, not rejected, or just everything not deleted)
        // Since we are looking for "Posted" mainly for TenDayPostingRule, we could filter by status='Posted'
        // But the prompt says "Loops through active services".
        
        $services = Service::whereNotIn('status', ['Released', 'Rejected', 'Completed'])
            ->get();

        $count = 0;
        foreach ($services as $service) {
            try {
                $automation->handleScheduledCheck($service);
                $count++;
            } catch (\Exception $e) {
                Log::error("Error processing service #{$service->id} in automation: " . $e->getMessage());
                $this->error("Error processing service #{$service->id}");
            }
        }

        $this->info("Processed {$count} services.");
        return Command::SUCCESS;
    }
}
