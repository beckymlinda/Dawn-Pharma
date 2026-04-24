<?php

namespace App\Helpers;

class PaymentMethods
{
    /**
     * Get all available payment methods
     */
    public static function getAllMethods()
    {
        return [
            'cash',
            'card', 
            'cheque',
            'bank_transfer',
            'advance',
            'national_bank',
            'standard_bank',
            'airtel_money',
            'mpamba',
            'other'
        ];
    }
    
    /**
     * Get payment method display names
     */
    public static function getDisplayNames()
    {
        return [
            'cash' => 'Cash',
            'card' => 'Card',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'advance' => 'Advance',
            'national_bank' => 'National Bank',
            'standard_bank' => 'Standard Bank',
            'airtel_money' => 'Airtel Money',
            'mpamba' => 'TNM Mpamba',
            'other' => 'Other'
        ];
    }
    
    /**
     * Initialize payment method totals array
     */
    public static function initializeTotals()
    {
        $methods = self::getAllMethods();
        $totals = [];
        
        foreach ($methods as $method) {
            $totals[$method] = 0;
        }
        
        return $totals;
    }
    
    /**
     * Calculate payment method totals from sales collection
     */
    public static function calculateTotals($sales)
    {
        $totals = self::initializeTotals();
        
        foreach ($sales as $sale) {
            $method = strtolower($sale->payment_method);
            
            if (isset($totals[$method])) {
                $totals[$method] += $sale->total_amount;
            } else {
                $totals['other'] += $sale->total_amount;
            }
        }
        
        return $totals;
    }
}
