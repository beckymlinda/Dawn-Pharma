<?php

/*
 * Defaults for the business details shown on screens, receipts and reports.
 * Each client changes these under Settings (stored in the `settings` table);
 * these values are only used until that has been filled in, so a new
 * installation never shows another client's name or phone number.
 */
return [

    'system_name' => env('BUSINESS_NAME', env('APP_NAME', 'POS')),

    'location' => env('BUSINESS_LOCATION', ''),

    'telephone' => env('BUSINESS_TELEPHONE', ''),

    'email' => env('BUSINESS_EMAIL', ''),

    // TPIN / VAT registration number printed on receipts
    'tax_number' => env('BUSINESS_TAX_NUMBER', ''),

    'receipt_footer' => env('RECEIPT_FOOTER', 'Thank you for your purchase!'),

    // Software vendor credit shown in small print on receipts and reports
    'powered_by' => env('POWERED_BY', 'EDUC Consultancy'),

    'primary_color' => '#004d3d',

    'secondary_color' => '#006b54',

];
