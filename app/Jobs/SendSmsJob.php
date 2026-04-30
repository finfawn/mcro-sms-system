<?php

namespace App\Jobs;

use App\Models\Service;
use App\Models\SmsMessage;
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
    public ?int $smsMessageId;
    public $tries = 3;
    public $timeout = 120;

    public function __construct(int $serviceId, string $eventKey, ?int $smsMessageId = null)
    {
        $this->serviceId = $serviceId;
        $this->eventKey = $eventKey;
        $this->smsMessageId = $smsMessageId;
    }

    public static function dispatchFor(Service $service, string $eventKey): void
    {
        $pending = app(SmsService::class)->createPending($service, $eventKey);
        self::dispatch($service->id, $eventKey, $pending?->id);
    }

    public function handle(SmsService $sms): void
    {
        $service = Service::find($this->serviceId);
        if (!$service) {
            return;
        }
        $smsMessage = $this->smsMessageId ? SmsMessage::find($this->smsMessageId) : null;
        $message = $sms->send($service, $this->eventKey, $smsMessage);
        if ($message && in_array($message->status, ['queued', 'sent'], true)) {
            $this->markServiceEventSent($service);
        }
        $ms = (int) (env('SMS_RATE_MS', 1000));
        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    protected function markServiceEventSent(Service $service): void
    {
        if (in_array($this->eventKey, ['posting_notice', 'petition_ready_for_posting'], true)) {
            $service->sms_posting_sent = true;
        }
        if (in_array($this->eventKey, ['ready_for_pickup', 'ready_for_release', 'psa_no_feedback_uploaded'], true)) {
            $service->sms_ready_sent = true;
        }
        if ($this->eventKey === 'releasing') {
            $service->sms_release_sent = true;
        }
        if ($service->isDirty()) {
            $service->save();
        }
    }
}
