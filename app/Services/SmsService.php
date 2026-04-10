<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $sms;

    public function __construct()
    {
        $AT = new AfricasTalking(
            config('services.africastalking.username'),
            config('services.africastalking.api_key')
        );

        $this->sms = $AT->sms();
    }

    public function sendNudge(string $phone, string $tenantName, float $amount): bool
    {
        try {

            $message = "Dear {$tenantName}, your rent balance of KES "
                . number_format($amount, 0)
                . " is overdue. Please arrange payment as soon as possible. - EstateFlow";

            $result = $this->sms->send([
                'to' => $this->formatPhone($phone),
                'message' => $message,
            ]);

            Log::info('SMS reminder sent', [
                'phone' => $phone,
                'result' => $result
            ]);

            return true;

        } catch (\Exception $e) {

            Log::error('SMS reminder failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        return '+' . $phone;
    }
}