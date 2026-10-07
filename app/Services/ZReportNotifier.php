<?php

namespace App\Services;

use App\Mail\ZReportMail;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\Register;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ZReportNotifier
{
    public const ENABLED_SETTING = 'notify_zreport_enabled';

    public static function isEnabled(): bool
    {
        return Setting::where('key', self::ENABLED_SETTING)->value('value') !== '0';
    }

    /**
     * Who receives the Z report: all active recipients. If none are set up yet,
     * fall back to the login email of the user who closed the register.
     */
    public function recipientsFor(Register $register): array
    {
        $emails = NotificationRecipient::active()->pluck('email')->all();

        if (empty($emails) && $register->user?->email) {
            $emails = [$register->user->email];
        }

        return array_values(array_unique($emails));
    }

    /**
     * Email the Z report for a register and record the outcome.
     */
    public function send(Register $register, ?array $recipients = null, ?int $triggeredBy = null): NotificationLog
    {
        $recipients ??= $this->recipientsFor($register);
        $mail = new ZReportMail((new ZReportService())->build($register));
        $subject = $mail->envelope()->subject;

        if (empty($recipients)) {
            return $this->log($register, [], $subject, 'failed', 'No recipients configured.', $triggeredBy);
        }

        try {
            Mail::to($recipients)->send($mail);

            return $this->log($register, $recipients, $subject, 'sent', null, $triggeredBy);
        } catch (\Throwable $e) {
            Log::error('Z report email failed', ['register_id' => $register->id, 'error' => $e->getMessage()]);

            return $this->log($register, $recipients, $subject, 'failed', $e->getMessage(), $triggeredBy);
        }
    }

    private function log(Register $register, array $recipients, ?string $subject, string $status, ?string $error, ?int $triggeredBy): NotificationLog
    {
        return NotificationLog::create([
            'type' => 'z_report',
            'register_id' => $register->id,
            'recipients' => implode(', ', $recipients),
            'subject' => $subject,
            'status' => $status,
            'error' => $error ? mb_substr($error, 0, 2000) : null,
            'triggered_by' => $triggeredBy,
        ]);
    }
}
