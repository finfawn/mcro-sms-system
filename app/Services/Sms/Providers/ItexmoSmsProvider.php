<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

class ItexmoSmsProvider implements SmsProvider
{
    protected string $apiCode;
    protected ?string $password;
    protected ?string $email;
    protected ?string $sender;

    public function __construct(?string $apiCode, ?string $password = null, ?string $sender = null, ?string $email = null)
    {
        $this->apiCode = (string) $apiCode;
        $this->password = $password ? (string) $password : null;
        $this->sender = $sender ? (string) $sender : null;
        $this->email = $email ? (string) $email : null;
    }

    public function send(string $to, string $message): void
    {
        if (!$this->apiCode) {
            throw new \RuntimeException('Itexmo configuration missing');
        }
        $url = 'https://www.itexmo.com/php_api/api.php';
        $payload = [
            '1' => ltrim($to, '+'),
            '2' => $message,
            '3' => $this->apiCode,
        ];
        if ($this->password) {
            $payload['passwd'] = $this->password;
        }
        if ($this->sender) {
            $payload['senderid'] = $this->sender;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_USERAGENT, 'mcro-sms-system/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $code = is_string($response) ? trim($response) : '';
        if ($err || $httpCode < 200 || $httpCode >= 300 || $code === '') {
            Log::error('Itexmo send attempt (php_api) failed', ['http' => $httpCode, 'err' => $err, 'code' => $code !== '' ? $code : null]);
            if ($this->email && $this->password) {
                $this->sendViaRest($to, $message);
                return;
            }
            throw new \RuntimeException('Itexmo SMS send failed');
        }
        if ($code !== '0') {
            Log::error('Itexmo non-zero code', ['code' => $code]);
            throw new \RuntimeException('Itexmo returned error code ' . $code);
        }
        Log::info('Itexmo SMS queued');
    }

    protected function sendViaRest(string $to, string $message): void
    {
        $url = 'https://api.itexmo.com/api/broadcast';
        $body = [
            'email' => $this->email,
            'password' => $this->password,
            'api_code' => $this->apiCode,
            'recipients' => [ltrim($to, '+')],
            'message' => $message,
        ];
        if ($this->sender) {
            $body['sender_id'] = $this->sender;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($err || $httpCode < 200 || $httpCode >= 300) {
            Log::error('Itexmo REST send failed', ['http' => $httpCode, 'err' => $err, 'resp' => $response]);
            throw new \RuntimeException('Itexmo REST send failed');
        }
        $json = null;
        try { $json = json_decode((string)$response, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) {}
        if (!is_array($json) || !($json['success'] ?? false)) {
            Log::error('Itexmo REST non-success', ['resp' => $response]);
            throw new \RuntimeException('Itexmo REST returned error');
        }
        Log::info('Itexmo REST SMS queued');
    }
}
