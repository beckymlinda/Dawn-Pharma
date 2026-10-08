<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup (needs the cPanel cron job: php artisan schedule:run, every minute)
Schedule::command('backup:database')->dailyAt('23:30')->withoutOverlapping();

// Proof-of-payment emails the mail server refused (e.g. Gmail "421 busy") are tried again
Artisan::command('payments:resend-proof-emails', function () {
    $unsent = App\Models\PaymentSubmission::whereNull('notified_at')
        ->where('created_at', '>=', now()->subDays(7))
        ->where('notify_attempts', '<', 15)
        ->get();
    foreach ($unsent as $submission) {
        $ok = app(App\Services\PaymentProofNotifier::class)->send($submission, retry: false);
        $this->line(($ok ? 'sent ' : 'failed ') . $submission->id);
    }
})->purpose('Retry proof-of-payment emails that could not be sent');
Schedule::command('payments:resend-proof-emails')->everyTenMinutes()->withoutOverlapping();
