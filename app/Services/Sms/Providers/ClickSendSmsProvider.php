<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

class ClickSendSmsProvider implements SmsProvider
{
    protected string $username;
    protected string $apiKey;
    protected ?string $from;

    public function __construct(?string $username, ?string $apiKey, ?string $from = null)
    {
        $this->username = (string) $username;
        $this->apiKey = (string) $apiKey;
        $this->from = $from ? (string) $from : null;
    }

    public function send(string $to, string $message): void
    {
        if (!$this->username || !$this->apiKey) {
            throw new \RuntimeException('ClickSend configuration missing');
        }
        $url = 'https://rest.clicksend.com/v3/sms/send';
        $msg = [
            'to' => $to,
            'body' => $message,
            'source' => 'mcro-sms-system',
        ];
        if ($this->from) {
            $msg['from'] = $this->from;
        }
        $payload = json_encode(['messages' => [$msg]]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->apiKey);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err || $code < 200 || $code >= 300) {
            Log::error('ClickSend SMS error', ['code' => $code, 'error' => $err, 'response' => $response]);
            throw new \RuntimeException('ClickSend SMS send failed');
        }
        $json = null;
        try { $json = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) {}
        if (!is_array($json) || (($json['http_code'] ?? null) !== 200 && ($json['response_code'] ?? '') !== 'SUCCESS')) {
            Log::error('ClickSend non-success', ['response' => $response]);
            throw new \RuntimeException('ClickSend returned error');
        }
        Log::info('ClickSend SMS queued');
    }
}
