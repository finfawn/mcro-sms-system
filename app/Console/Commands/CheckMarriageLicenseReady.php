<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Services\SmsService;
use Illuminate\Support\Carbon;

class CheckMarriageLicenseReady extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marriage:check-ready';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Ready for Pickup SMS for Marriage License applications after 10 days posting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $todayMinus10 = Carbon::today()->subDays(10);
        $services = Service::query()
            ->where('service_type', 'Application for Marriage License')
            ->where('status', 'Posted')
            ->whereNotNull('posting_start_date')
            ->whereDate('posting_start_date', '<=', $todayMinus10->toDateString())
            ->where('sms_ready_sent', false)
            ->get();
        $sms = new SmsService();
        foreach ($services as $service) {
            $sms->send($service, 'releasing');
            $service->sms_ready_sent = true;
            $service->save();
            $this->info("Ready SMS logged for service #{$service->id}");
        }
        return self::SUCCESS;
    }
}
