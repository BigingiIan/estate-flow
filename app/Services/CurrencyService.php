<?php

namespace App\Services;

class CurrencyService
{
    protected array $currencies = [
        'KES' => ['symbol' => 'KES', 'rate' => 1.0,      'locale' => 'en_KE'],
        'USD' => ['symbol' => 'USD', 'rate' => 0.0077,   'locale' => 'en_US'],
        'GBP' => ['symbol' => 'GBP', 'rate' => 0.0061,   'locale' => 'en_GB'],
        'EUR' => ['symbol' => 'EUR', 'rate' => 0.0071,   'locale' => 'en_EU'],
    ];

    public function getCode(): string
    {
        return session('estateflow_prefs.currency', 'KES');
    }

    public function getSymbol(): string
    {
        return $this->currencies[$this->getCode()]['symbol'] ?? 'KES';
    }

    public function getRate(): float
    {
        return $this->currencies[$this->getCode()]['rate'] ?? 1.0;
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

        if ($converted >= 1_000_000) {
            return $symbol . ' ' . number_format($converted / 1_000_000, 1) . 'M';
        }
        if ($converted >= 1_000) {
            return $symbol . ' ' . number_format($converted / 1_000, 1) . 'k';
        }

        return $symbol . ' ' . number_format($converted, 0);
    }

    public function allCurrencies(): array
    {
        return array_keys($this->currencies);
    }
}