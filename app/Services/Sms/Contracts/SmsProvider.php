<?php

namespace App\Services\Sms\Contracts;

interface SmsProvider
{
    public function send(string $to, string $message): void;
}
