<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/**
 * Sends SMS through TumaSend (https://devs.tumasend.com/docs).
 * POST {url}/send/sms with the x-api-key header; numbers in +265… form.
 */
class TumaSend
{
    public static function isConfigured(): bool
    {
        return (bool) config('services.tumasend.key');
    }

    /**
     * Malawi numbers as people type them (0991 234 567, 265991234567,
     * +265 991-234-567) to +265991234567. Other international numbers must
     * start with +. Returns null when the number is not valid.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        $international = str_starts_with(trim((string) $phone), '+');

        if (preg_match('/^0([89]\d{8})$/', $digits, $m) || preg_match('/^([89]\d{8})$/', $digits, $m)) {
            return '+265' . $m[1];
        }
        if (preg_match('/^265([89]\d{8})$/', $digits, $m)) {
            return '+265' . $m[1];
        }
        if ($international && !str_starts_with($digits, '265') && preg_match('/^\d{9,15}$/', $digits)) {
            return '+' . $digits;
        }

        return null;
    }

    /**
     * @return array{ok: bool, error: ?string, credits_remaining: ?int}
     */
    public function send(array $phones, string $message): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => 'SMS is not set up (TUMASEND_API_KEY is missing).', 'credits_remaining' => null];
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders(['x-api-key' => config('services.tumasend.key')])
                ->post(rtrim(config('services.tumasend.url'), '/') . '/send/sms', [
                    'from' => config('services.tumasend.from'),
                    'recipients' => array_values($phones),
                    // 918 characters is the longest message TumaSend accepts
                    'message' => mb_substr($message, 0, 918),
                ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach TumaSend: ' . $e->getMessage(), 'credits_remaining' => null];
        }

        $body = $response->json() ?? [];

        if ($response->successful() && ($body['success'] ?? true)) {
            $invalid = $body['invalid_recipients'] ?? [];

            return [
                'ok' => true,
                'error' => $invalid ? 'Not sent to: ' . implode(', ', (array) $invalid) : null,
                'credits_remaining' => $body['credits_remaining'] ?? null,
            ];
        }

        $reason = match ($response->status()) {
            401 => 'The TumaSend API key was refused.',
            402 => 'Not enough SMS credits on TumaSend. Top up and try again.',
            429 => 'TumaSend is limiting requests. Try again in a minute.',
            default => $body['message'] ?? $body['error'] ?? ('TumaSend error ' . $response->status()),
        };

        return ['ok' => false, 'error' => $reason, 'credits_remaining' => null];
    }
}
