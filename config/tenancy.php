<?php

/*
 * Multi-tenancy: one installation serves many shops. Each shop has a slug and
 * a link such as https://pos.example.com/area25. Opening the link (or entering
 * the slug as "Shop code" on the login page) selects the shop for that browser;
 * all other page addresses are the same for every shop.
 *
 * All shops share one database; every shop-owned row carries a tenant_id and is
 * filtered automatically. The admin portal for the software owner is /platform.
 */
return [

    // Shop used when the browser has not selected one (e.g. an installation that
    // serves a single shop, or local development). Leave empty to always ask.
    'fallback_slug' => env('TENANCY_DEFAULT_SLUG', ''),

    // Slugs that can never be given to a shop (on top of every page address in the app)
    'reserved_slugs' => ['admin', 'platform', 'setup', 'login', 'logout', 'register', 'api', 'www', 'storage',
        'assets', 'css', 'js', 'images', 'build', 'vendor', 'shop', 'shops', 'help', 'support'],

    // Days a shop keeps working after its paid-until date before it is blocked
    'default_grace_days' => (int) env('TENANCY_GRACE_DAYS', 7),

    // Warn shop admins this many days before the paid-until date
    'warn_days_before' => 7,

    // How shops pay you: shown on their Billing page and when the system is blocked
    'billing_contact' => [
        'name' => env('BILLING_CONTACT_NAME', env('POWERED_BY', 'Your system provider')),
        'phone' => env('BILLING_CONTACT_PHONE', ''),
        'email' => env('BILLING_CONTACT_EMAIL', ''),
        // e.g. "Airtel Money 0999 123 456 (R. Mulinda)" or bank details
        'pay_to' => env('BILLING_PAY_TO', ''),
    ],

    // Where shops send subscription payments (shown on their Billing page)
    'payment_accounts' => [
        'mpamba' => ['label' => 'TNM Mpamba', 'number' => '+265 882 446 802', 'name' => null, 'icon' => 'bi-phone'],
        'national_bank' => ['label' => 'National Bank', 'number' => '1012249353', 'name' => 'Rabecca Mulinda', 'icon' => 'bi-bank'],
        'airtel_money' => ['label' => 'Airtel Money', 'number' => '0990 516 395', 'name' => 'Daniel Pandor', 'icon' => 'bi-phone'],
    ],

    // Who is emailed when a shop uploads proof of payment
    'payment_notify_email' => env('BILLING_NOTIFY_EMAIL', 'beckymlinda@gmail.com'),

    // Plans offered (monthly price, shown in the portal and on shops' Billing page)
    'plans' => [
        'basic' => ['name' => 'Basic', 'price' => 25000],
        'standard' => ['name' => 'Standard', 'price' => 45000],
        'premium' => ['name' => 'Premium', 'price' => 75000],
    ],

    // Tables whose rows belong to a shop (used to scope validation rules such as exists:/unique:)
    'tables' => [
        'users', 'units', 'categories', 'brands', 'products', 'contacts', 'purchases', 'purchase_items',
        'sales', 'sale_items', 'pos', 'locations', 'registers', 'stock_histories', 'roles',
        'stock_adjustments', 'payments', 'activity_logs', 'settings', 'notification_recipients',
        'notification_logs', 'integrity_events', 'expenses', 'sms_recipients',
    ],

];
