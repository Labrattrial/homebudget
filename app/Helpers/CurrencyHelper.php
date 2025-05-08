<?php

namespace App\Helpers;

class CurrencyHelper
{
    private static $exchangeRates = [
        'PHP' => [
            'USD' => 0.018,  // 1 PHP = 0.018 USD
            'EUR' => 0.016,  // 1 PHP = 0.016 EUR
            'GBP' => 0.014,  // 1 PHP = 0.014 GBP
            'JPY' => 2.67,   // 1 PHP = 2.67 JPY
            'AUD' => 0.027,  // 1 PHP = 0.027 AUD
            'PHP' => 1.0     // 1 PHP = 1 PHP
        ],
        'USD' => [
            'PHP' => 55.5,   // 1 USD = 55.5 PHP
            'EUR' => 0.92,   // 1 USD = 0.92 EUR
            'GBP' => 0.79,   // 1 USD = 0.79 GBP
            'JPY' => 148.5,  // 1 USD = 148.5 JPY
            'AUD' => 1.52,   // 1 USD = 1.52 AUD
            'USD' => 1.0     // 1 USD = 1 USD
        ],
        'EUR' => [
            'PHP' => 60.3,   // 1 EUR = 60.3 PHP
            'USD' => 1.09,   // 1 EUR = 1.09 USD
            'GBP' => 0.86,   // 1 EUR = 0.86 GBP
            'JPY' => 161.4,  // 1 EUR = 161.4 JPY
            'AUD' => 1.65,   // 1 EUR = 1.65 AUD
            'EUR' => 1.0     // 1 EUR = 1 EUR
        ],
        'GBP' => [
            'PHP' => 70.1,   // 1 GBP = 70.1 PHP
            'USD' => 1.27,   // 1 GBP = 1.27 USD
            'EUR' => 1.16,   // 1 GBP = 1.16 EUR
            'JPY' => 187.7,  // 1 GBP = 187.7 JPY
            'AUD' => 1.92,   // 1 GBP = 1.92 AUD
            'GBP' => 1.0     // 1 GBP = 1 GBP
        ],
        'JPY' => [
            'PHP' => 0.37,   // 1 JPY = 0.37 PHP
            'USD' => 0.0067, // 1 JPY = 0.0067 USD
            'EUR' => 0.0062, // 1 JPY = 0.0062 EUR
            'GBP' => 0.0053, // 1 JPY = 0.0053 GBP
            'AUD' => 0.0102, // 1 JPY = 0.0102 AUD
            'JPY' => 1.0     // 1 JPY = 1 JPY
        ],
        'AUD' => [
            'PHP' => 36.5,   // 1 AUD = 36.5 PHP
            'USD' => 0.66,   // 1 AUD = 0.66 USD
            'EUR' => 0.61,   // 1 AUD = 0.61 EUR
            'GBP' => 0.52,   // 1 AUD = 0.52 GBP
            'JPY' => 98.0,   // 1 AUD = 98.0 JPY
            'AUD' => 1.0     // 1 AUD = 1 AUD
        ]
    ];

    public static function format($amount, $currency = null)
    {
        if ($currency === null) {
            $currency = auth()->user()->currency ?? 'PHP';
        }

        $formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, self::getCurrencySymbol($currency));
        
        return $formatter->format($amount);
    }

    public static function getCurrencySymbol($currency)
    {
        $symbols = [
            'PHP' => '₱',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$'
        ];

        return $symbols[$currency] ?? $currency;
    }

    public static function getCurrencyName($currency)
    {
        $names = [
            'PHP' => 'Philippine Peso',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
            'AUD' => 'Australian Dollar'
        ];

        return $names[$currency] ?? $currency;
    }

    public static function convert($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        if (!isset(self::$exchangeRates[$fromCurrency][$toCurrency])) {
            throw new \Exception("Exchange rate not found for {$fromCurrency} to {$toCurrency}");
        }

        return $amount * self::$exchangeRates[$fromCurrency][$toCurrency];
    }

    public static function getExchangeRate($fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        if (!isset(self::$exchangeRates[$fromCurrency][$toCurrency])) {
            throw new \Exception("Exchange rate not found for {$fromCurrency} to {$toCurrency}");
        }

        return self::$exchangeRates[$fromCurrency][$toCurrency];
    }

    public static function convertAmount($amount, $fromCurrency, $toCurrency)
    {
        $convertedAmount = self::convert($amount, $fromCurrency, $toCurrency);
        return number_format($convertedAmount, 2, '.', ',');
    }

    public static function getFormattedExchangeRate($fromCurrency, $toCurrency)
    {
        $rate = self::getExchangeRate($fromCurrency, $toCurrency);
        return number_format($rate, 4, '.', ',');
    }

    public static function getAllCurrencies()
    {
        return array_keys(self::$exchangeRates);
    }

    public static function getCurrencyInfo($currency)
    {
        return [
            'code' => $currency,
            'name' => self::getCurrencyName($currency),
            'symbol' => self::getCurrencySymbol($currency)
        ];
    }
} 