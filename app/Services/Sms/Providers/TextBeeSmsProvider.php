<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

class TextBeeSmsProvider implements SmsProvider
{
    protected string $baseUrl;
    protected string $deviceId;
    protected string $apiKey;

    public function __construct(?string $baseUrl, ?string $deviceId, ?string $apiKey)
    {
        $this->baseUrl = rtrim((string) ($baseUrl ?: 'https://api.textbee.dev'), '/');
        $this->deviceId = (string) $deviceId;
        $this->apiKey = (string) $apiKey;
    }

    public function send(string $to, string $message): void
    {
        if (!$this->deviceId || !$this->apiKey) {
            throw new \RuntimeException('TextBee configuration missing');
        }
        $url = $this->baseUrl . '/api/v1/gateway/devices/' . rawurlencode($this->deviceId) . '/send-sms';
        $payload = json_encode([
            'recipients' => [$to],
            'message' => $message,
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'x-api-key: ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err || $code < 200 || $code >= 300) {
            Log::error('TextBee SMS error', ['code' => $code, 'error' => $err, 'response' => $response]);
            throw new \RuntimeException('TextBee SMS send failed');
        }
        $json = null;
        try { $json = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) {}
        if (!is_array($json)) {
            Log::error('TextBee non-JSON response', ['response' => $response]);
            throw new \RuntimeException('TextBee returned invalid response');
        }
        Log::info('TextBee SMS queued');
    }
}
