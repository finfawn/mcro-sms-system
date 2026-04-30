<?php

namespace App\Services;

use App\Models\Service;
use App\Models\SmsTemplate;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;
use App\Services\Sms\Contracts\SmsProvider;
use App\Services\Sms\Providers\LogSmsProvider;
use App\Services\Sms\Providers\ItexmoSmsProvider;
use App\Services\Sms\Providers\ClickSendSmsProvider;
use App\Services\Sms\Providers\TextBeeSmsProvider;
use Illuminate\Support\Arr;

class SmsService
{
    protected SmsProvider $provider;

    public function __construct()
    {
        $provider = config('sms.provider', 'log');
        if ($provider === 'clicksend') {
            $cfg = config('sms.clicksend', []);
            $username = Arr::get($cfg, 'username');
            $apiKey = Arr::get($cfg, 'api_key');
            $from = Arr::get($cfg, 'from');
            $this->provider = new ClickSendSmsProvider($username, $apiKey, $from);
        } elseif ($provider === 'itexmo') {
            $cfg = config('sms.itexmo', []);
            $code = Arr::get($cfg, 'api_code');
            $password = Arr::get($cfg, 'password');
            $email = Arr::get($cfg, 'email');
            $sender = Arr::get($cfg, 'sender');
            $this->provider = new ItexmoSmsProvider($code, $password, $sender, $email);
        } elseif ($provider === 'textbee') {
            $cfg = config('sms.textbee', []);
            $base = Arr::get($cfg, 'base_url');
            $deviceId = Arr::get($cfg, 'device_id');
            $apiKey = Arr::get($cfg, 'api_key');
            $this->provider = new TextBeeSmsProvider($base, $deviceId, $apiKey);
        } else {
            $this->provider = new LogSmsProvider();
        }
    }

    public function createPending(Service $service, string $event_key): ?SmsMessage
    {
        $message = $this->buildMessage($service, $event_key);
        if (!$message) {
            Log::warning('SMS not queued because no template/fallback body was found', [
                'service_id' => $service->id,
                'event_key' => $event_key,
            ]);
            return null;
        }

        return SmsMessage::create([
            'service_id' => $service->id,
            'to' => $message['to'],
            'body' => $message['body'],
            'provider' => (string)config('sms.provider', 'log'),
            'event_key' => $event_key,
            'status' => 'pending',
            'error' => null,
        ]);
    }

    public function send(Service $service, string $event_key, ?SmsMessage $smsMessage = null): ?SmsMessage
    {
        $message = $this->buildMessage($service, $event_key);
        if (!$message) {
            if ($smsMessage) {
                $smsMessage->update([
                    'status' => 'failed',
                    'error' => 'No SMS template or fallback body was found',
                ]);
            }
            return $smsMessage;
        }

        $to = $message['to'];
        $body = $message['body'];

        if ($smsMessage) {
            $smsMessage->update([
                'to' => $to,
                'body' => $body,
                'provider' => (string)config('sms.provider', 'log'),
                'event_key' => $event_key,
                'status' => 'processing',
                'error' => null,
            ]);
        }

        try {
            $this->provider->send($to, $body);
            $data = [
                'service_id' => $service->id,
                'to' => $to,
                'body' => $body,
                'provider' => (string)config('sms.provider', 'log'),
                'event_key' => $event_key,
                'status' => (bool)config('sms.mark_sent_on_accept', false) ? 'sent' : 'queued',
                'error' => null,
            ];
            $smsMessage = $smsMessage
                ? tap($smsMessage)->update($data)
                : SmsMessage::create($data);
            Log::info('SMS queued', [
                'to' => $to,
                'service_id' => $service->id,
                'event_key' => $event_key,
            ]);
        } catch (\Throwable $e) {
            $data = [
                'service_id' => $service->id,
                'to' => $to,
                'body' => $body,
                'provider' => (string)config('sms.provider', 'log'),
                'event_key' => $event_key,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            $smsMessage = $smsMessage
                ? tap($smsMessage)->update($data)
                : SmsMessage::create($data);
            Log::error('SMS send failed', [
                'to' => $to,
                'service_id' => $service->id,
                'event_key' => $event_key,
                'error' => $e->getMessage(),
            ]);
        }

        return $smsMessage;
    }

    protected function buildMessage(Service $service, string $event_key): ?array
    {
        $template = SmsTemplate::where('service_type', $service->service_type)
            ->where('event_key', $event_key)
            ->where('is_active', true)
            ->first();
        if (!$template && in_array($service->service_type, config('sms.frontline_service_types', []), true)) {
            $template = SmsTemplate::whereIn('service_type', config('sms.frontline_service_types', []))
                ->where('event_key', $event_key)
                ->where('is_active', true)
                ->first();
        }
        $body = $template ? $template->template_body : $this->fallbackBody($service, $event_key);
        if (!$body) {
            return null;
        }
        $body = str_replace('{{citizen_name}}', $service->citizen_name, $body);
        $body = str_replace('{{reference_no}}', $service->reference_no, $body);

        return [
            'to' => $this->normalizeRecipient($service->mobile_number),
            'body' => $body,
        ];
    }

    protected function normalizeRecipient(string $to): string
    {
        $t = trim($to);
        if (str_starts_with($t, '+')) {
            return $t;
        }
        $digits = preg_replace('/\D+/', '', $t);
        if (str_starts_with($digits, '63')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+63' . substr($digits, 1);
        }
        return '+' . $digits;
    }

    protected function fallbackBody(Service $service, string $event_key): ?string
    {
        if ($service->service_type === 'Application for Marriage License') {
            if ($event_key === 'posting_notice') {
                return 'Your Application for Marriage License has been posted today. Your Marriage License shall be issued after the 10 days posting of the notice.';
            }
            if ($event_key === 'releasing') {
                return 'Good day! Your Marriage License is now available for release. Please claim it at the MCRO office during office hours.';
            }
        }
        $frontlineTypes = config('sms.frontline_service_types', []);
        if (in_array($service->service_type, $frontlineTypes, true) && $event_key === 'ready_for_pickup') {
            return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your document is ready for pick up. Please bring your claim stub to the MCRO office during office hours.';
        }
        if ($service->service_type === 'Endorsement for Negative PSA - Positive LCRO') {
            if ($event_key === 'psa_sent') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been sent to PSA-RSSO CAR for uploading into the PSA database. We will notify again once approved.';
            }
            if ($event_key === 'psa_no_feedback_uploaded') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been successfully uploaded into the PSA database. You may now request for your PSA document in our office.';
            }
            if ($event_key === 'psa_feedback_received') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). PSA sent back your documents with feedback for further processing. The document is not yet ready for release. We will process and send to PSA again.';
            }
            if ($event_key === 'psa_resent_for_processing') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been reworked and resent to PSA for processing.';
            }
        }
        if ($service->service_type === 'Endorsement for Blurred PSA - Clear LCRO File') {
            if ($event_key === 'psa_sent') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been sent to PSA-RSSO CAR for replacement of your file in the PSA database.';
            }
            if ($event_key === 'psa_no_feedback_uploaded') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been approved for uploading into the PSA database. You may now request your new PSA document from our office.';
            }
            if ($event_key === 'psa_feedback_received') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). PSA sent back your documents for further processing. The document is not yet ready for release. We will process and send to PSA again.';
            }
            if ($event_key === 'psa_resent_for_processing') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been reworked and resent to PSA for uploading into the PSA database.';
            }
        }
        if ($service->service_type === 'Endorsement of Legal Instrument & MC 2010-04 & Court Order') {
            if ($event_key === 'psa_sent') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been forwarded to PSA Central office for uploading to the PSA database.';
            }
            if ($event_key === 'psa_no_feedback_uploaded') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been approved for uploading into the PSA database. You may now request your new PSA document from our office.';
            }
            if ($event_key === 'psa_feedback_received') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). PSA sent back your documents for further processing. The document is not yet ready for release. We will process and send to PSA again.';
            }
            if ($event_key === 'psa_resent_for_processing') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your documents have been reworked and resent to PSA for uploading into the PSA database.';
            }
        }
        if ($service->service_type === 'Petitions filed under RA 9048 - Clerical Error') {
            if ($event_key === 'petition_ready_for_filing') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition is ready for filing. Please come to the MCRO office to sign and pay at the Treasury Office for filing and processing fees.';
            }
            if ($event_key === 'petition_ready_for_posting') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been filed and is now ready for 10-day posting. Posting starts one business day after filing.';
            }
            if ($event_key === 'sent_to_psa_legal') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been sent to PSA Legal Services for review.';
            }
            if ($event_key === 'psa_affirmed') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been affirmed by PSA. Please come to the MCRO office within 15 days from receipt of this notification.';
            }
            if ($event_key === 'psa_impugned') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been impugned by PSA. Please come to the MCRO office for further processing.';
            }
            if ($event_key === 'psa_resent_for_review') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been reworked and resent to PSA Legal Services for review.';
            }
        }
        if ($service->service_type === 'Petitions filed under RA 9048 & RA 10172') {
            if ($event_key === 'petition_ready_for_filing') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition is ready for filing. Please come to the MCRO office to sign and pay at the Treasury Office for filing and processing fees.';
            }
            if ($event_key === 'petition_ready_for_posting') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been filed and is now ready for 10-day posting. Posting starts one business day after filing.';
            }
            if ($event_key === 'petition_published') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been published for two consecutive weeks. Decision shall be rendered 7 business days after the last publication date.';
            }
            if ($event_key === 'sent_to_psa_legal') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition decision has been sent to PSA Legal Services for review.';
            }
            if ($event_key === 'psa_affirmed') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been affirmed by PSA. Please come to the MCRO office within 15 days from receipt of this notification.';
            }
            if ($event_key === 'psa_impugned') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been impugned by PSA. Please come to the MCRO office for further processing.';
            }
            if ($event_key === 'psa_resent_for_review') {
                return 'Good day {{citizen_name}} (Ref: {{reference_no}}). Your petition has been reworked and resent to PSA Legal Services for review.';
            }
        }
        if (in_array($service->service_type, [
            'Delayed Registration',
            'Delayed Registration of Birth',
            'Delayed Registration of Death',
            'Delayed Registration of Marriage',
        ], true)) {
            if ($event_key === 'verification_started') {
                return 'We received your Delayed Registration requirements for {{citizen_name}} (Ref: {{reference_no}}). Your application is now subject to verification.';
            }
            if ($event_key === 'requirements_incomplete') {
                return 'Your Delayed Registration requirements for {{citizen_name}} (Ref: {{reference_no}}) are incomplete. Please complete the required documents at the MCRO to proceed.';
            }
            if ($event_key === 'verification_consistent') {
                return 'Verification complete for {{citizen_name}} (Ref: {{reference_no}}). Your application is consistent. We will proceed to posting notice, which undergoes a 10-day posting period.';
            }
        }
        return null;
    }
}
    
