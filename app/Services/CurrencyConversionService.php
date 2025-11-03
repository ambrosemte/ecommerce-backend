<?php

namespace App\Services;

class CurrencyConversionService
{
    /**
     * Exchange rates relative to NGN (₦).
     * You can later replace this with a dynamic API call.
     */
    protected $exchangeRates = [
        'NGN' => 1,
        'USD' => 0.0012, // Example: ₦1 = $0.0012
        'EUR' => 0.0011,
        'GBP' => 0.0010,
    ];

    /**
     * Convert a price in NGN to the user's preferred currency.
     *
     * @param  int  $amount
     * @param  string     $currency
     * @return array
     */
    public function convert(int $amount, string $currency): array
    {
        if ($currency == '') {
            $currency = 'NGN';
        }

        $currency = strtoupper($currency);

        // Default to NGN if currency not found
        $rate = $this->exchangeRates[$currency] ?? 1;

        $convertedAmount = $amount * $rate;

        return [
            'amount' => round($convertedAmount, 2),
            'currency' => $currency,
            'rate' => $rate,
        ];
    }
}
