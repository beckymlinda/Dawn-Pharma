<?php

namespace App\Services;

use App\Mail\PaymentProofSubmitted;
use App\Models\PaymentSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the software owner about a new proof of payment. Mail servers (Gmail in
 * particular) sometimes refuse for a moment ("421 Server busy"), so it tries a few
 * times and records the outcome on the submission; anything still unsent is
 * retried by the scheduler (payments:resend-proof-emails) and can be resent from
 * the admin portal.
 */
class PaymentProofNotifier
{
    /** Seconds to wait before the 2nd and 3rd attempt */
    public static array $retryDelays = [3, 10];

    public function send(PaymentSubmission $submission, bool $retry = true): bool
    {
        $to = config('tenancy.payment_notify_email');
        if (!$to) {
            return false;
        }

        $delays = $retry ? self::$retryDelays : [];
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                Mail::to($to)->send(new PaymentProofSubmitted($submission->fresh('tenant')));
                $submission->forceFill([
                    'notified_at' => now(),
                    'notify_attempts' => $submission->notify_attempts + $attempt,
                    'notify_error' => null,
                ])->save();

                return true;
            } catch (\Throwable $e) {
                $delay = $delays[$attempt - 1] ?? null;
                if ($delay === null) {
                    $submission->forceFill([
                        'notify_attempts' => $submission->notify_attempts + $attempt,
                        'notify_error' => mb_substr($e->getMessage(), 0, 500),
                    ])->save();
                    Log::error('Payment proof email failed', ['submission' => $submission->id, 'attempts' => $attempt, 'error' => $e->getMessage()]);

                    return false;
                }
                sleep($delay);
            }
        }
    }
}
