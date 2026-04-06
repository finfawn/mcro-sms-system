<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $serviceId;
    public string $eventKey;
    public $tries = 3;
    public $timeout = 120;

    public function __construct(int $serviceId, string $eventKey)
    {
        $this->serviceId = $serviceId;
        $this->eventKey = $eventKey;
    }

    public function handle(SmsService $sms): void
    {
        $service = Service::find($this->serviceId);
        if (!$service) {
            return;
        }
        $sms->send($service, $this->eventKey);
        $ms = (int) (env('SMS_RATE_MS', 1000));
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }
}
