<?php

namespace App\Services;

use App\Mail\ZReportMail;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\Register;
use App\Models\Setting;
use App\Models\SmsRecipient;
use App\Services\Sms\TumaSend;
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

    /**
     * Text the Z report summary to the active phone numbers and record the outcome.
     * Returns null when no phone numbers are set up (nothing to send).
     */
    public function sendSms(Register $register, ?int $triggeredBy = null): ?NotificationLog
    {
        $phones = SmsRecipient::active()->pluck('phone')->unique()->values()->all();
        if (empty($phones)) {
            return null;
        }

        $result = app(TumaSend::class)->send($phones, $this->smsText((new ZReportService())->build($register)));

        if (!$result['ok']) {
            Log::error('Z report SMS failed', ['register_id' => $register->id, 'error' => $result['error']]);
        }

        return NotificationLog::create([
            'type' => 'z_report_sms',
            'register_id' => $register->id,
            'recipients' => implode(', ', $phones),
            'subject' => 'SMS',
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['error'] ? mb_substr($result['error'], 0, 2000) : null,
            'triggered_by' => $triggeredBy,
        ]);
    }

    /** Longest text that goes as a single SMS (plain GSM characters) */
    public const SMS_LIMIT = 160;

    /** Short names for payment methods in the SMS */
    private const SMS_METHODS = [
        'cash' => 'Cash', 'airtel_money' => 'Airtel', 'mpamba' => 'Mpamba', 'card' => 'Card',
        'bank_transfer' => 'Bank', 'national_bank' => 'NBM', 'standard_bank' => 'StdBank',
        'cheque' => 'Chq', 'cheques' => 'Chq', 'advance' => 'Adv', 'cash_and_card' => 'Other', 'other' => 'Other',
    ];

    /**
     * One-SMS Z report: each payment method with its amount, then the total.
     *   Area 25 Pharmacy Z32 08/10
     *   Cash 30,000
     *   Airtel 10,000
     *   Total MWK 45,000 (12 sales)
     */
    public function smsText(array $z): string
    {
        $mwk = fn ($v) => number_format((float) $v, 0);

        $methods = collect($z['payments'])->filter(fn ($p) => $p['amount'] > 0)
            ->map(fn ($p, $key) => ['name' => self::SMS_METHODS[$key] ?? \Illuminate\Support\Str::limit(\Illuminate\Support\Str::ascii($p['label']), 8, ''), 'amount' => $p['amount']])
            ->values();

        $tag = 'Z' . $z['register']->id . ' ' . $z['closed_at']->format('d/m');
        $total = 'Total MWK ' . $mwk($z['gross_sales']) . ' (' . $z['sales_count'] . ' sales)';

        $body = $total;
        // Many methods: keep the largest and add the rest together as "Other"
        for ($keep = $methods->count(); $keep >= 1; $keep--) {
            $shown = $methods->take($keep);
            if ($keep < $methods->count()) {
                $shown->push(['name' => 'Other', 'amount' => $methods->slice($keep)->sum('amount')]);
            }
            $body = $shown->map(fn ($m) => $m['name'] . ' ' . $mwk($m['amount']))->push($total)->implode("\n");
            if (mb_strlen($tag . "\n" . $body) <= self::SMS_LIMIT) {
                break;
            }
        }

        // Shop name in plain letters (accents would cut the SMS to 70 characters), shortened to fit
        $room = self::SMS_LIMIT - mb_strlen($tag . "\n" . $body) - 1;
        $name = trim(\Illuminate\Support\Str::limit(\Illuminate\Support\Str::ascii($z['business']['name'] ?? ''), max(0, $room), ''));

        return trim($name . ' ' . $tag) . "\n" . $body;
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
