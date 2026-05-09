<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    // Fallback rates if API is down
    protected array $fallbackRates = [
        'KES' => 1.0,
        'USD' => 0.0077,
        'GBP' => 0.0061,
        'EUR' => 0.0071,
    ];

    public function getCode(): string
    {
        return session('estateflow_prefs.currency', 'KES');
    }

    public function getSymbol(): string
    {
        $symbols = [
            'KES' => 'KES',
            'USD' => 'USD',
            'GBP' => 'GBP',
            'EUR' => 'EUR',
        ];
        return $symbols[$this->getCode()] ?? 'KES';
    }

    public function getRate(): float
    {
        if ($this->getCode() === 'KES') return 1.0;

        $rates = $this->getLiveRates();
        return $rates[$this->getCode()] ?? ($this->fallbackRates[$this->getCode()] ?? 1.0);
    }

    public function getLiveRates(): array
    {
        // Cache for 24 hours — 250 free requests/month is plenty
        return Cache::remember('exchange_rates_kes', 86400, function () {
            try {
                $apiKey = config('services.exchangerates.key');

                if (!$apiKey) {
                    return $this->fallbackRates;
                }

                $response = Http::timeout(5)->get(
                    "https://api.exchangeratesapi.io/v1/latest",
                    [
                        'access_key' => $apiKey,
                        'base'       => 'EUR', // free tier only supports EUR as base
                        'symbols'    => 'KES,USD,GBP,EUR',
                    ]
                );

                if (!$response->ok()) {
                    return $this->fallbackRates;
                }

                $data  = $response->json();
                $rates = $data['rates'] ?? [];

                // Convert all rates relative to KES
                $kesRate = $rates['KES'] ?? 130;
                return [
                    'KES' => 1.0,
                    'USD' => round(1 / ($kesRate / ($rates['USD'] ?? 0.77)), 6),
                    'GBP' => round(1 / ($kesRate / ($rates['GBP'] ?? 0.61)), 6),
                    'EUR' => round(1 / ($kesRate / ($rates['EUR'] ?? 1.00)), 6),
                ];

            } catch (\Exception $e) {
                Log::warning('Exchange rate fetch failed, using fallback', [
                    'error' => $e->getMessage()
                ]);
                return $this->fallbackRates;
            }
        });
    }

    public function format(float $amount): string
    {
        $converted = $amount * $this->getRate();
        $symbol    = $this->getSymbol();
        return $symbol . ' ' . number_format($converted, $this->getCode() === 'KES' ? 0 : 2);
    }

    public function formatShort(float $amount): string
    {
        $converted = $amount * $this->getRate();
        $symbol    = $this->getSymbol();

        if ($converted >= 1_000_000) return $symbol . ' ' . number_format($converted / 1_000_000, 1) . 'M';
        if ($converted >= 1_000)     return $symbol . ' ' . number_format($converted / 1_000, 1) . 'k';
        return $symbol . ' ' . number_format($converted, 0);
    }

    public function allCurrencies(): array
    {
        return array_keys($this->fallbackRates);
    }

    public function getRateDisplay(): string
    {
        if ($this->getCode() === 'KES') return '1 KES = 1 KES';
        $rate = $this->getRate();
        return "1 KES = {$this->getSymbol()} " . number_format($rate, 4);
    }
}