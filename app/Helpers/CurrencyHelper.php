<?php

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = null) {
        $config = config('currency');
        $symbol = $currency ?: $config['symbol'];
        $precision = $config['precision'];
        $decimalSeparator = $config['decimal_separator'];
        $thousandsSeparator = $config['thousands_separator'];
        $position = $config['position'];
        
        $formattedAmount = number_format($amount, $precision, $decimalSeparator, $thousandsSeparator);
        
        if ($position === 'before') {
            return $symbol . ' ' . $formattedAmount;
        } else {
            return $formattedAmount . ' ' . $symbol;
        }
    }
}

if (!function_exists('getCurrencySymbol')) {
    function getCurrencySymbol() {
        return config('currency.symbol');
    }
}
