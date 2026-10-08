<?php

namespace App\Http\Controllers;

use App\Mail\ZReportMail;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\Register;
use App\Models\Setting;
use App\Models\SmsRecipient;
use App\Services\Sms\TumaSend;
use App\Services\ZReportNotifier;
use App\Services\ZReportService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user->hasRole('super_admin') || $user->hasRole('admin'), 403);
    }

    public function index()
    {
        $this->authorizeAdmin();

        // The logged-in user's email is the default recipient the first time this is set up
        if (NotificationRecipient::count() === 0 && auth()->user()->email) {
            NotificationRecipient::create([
                'email' => strtolower(auth()->user()->email),
                'name' => auth()->user()->name,
                'is_default' => true,
                'added_by' => auth()->id(),
            ]);
        }

        $recipients = NotificationRecipient::with('addedBy')->orderByDesc('is_default')->orderBy('email')->get();
        $logs = NotificationLog::with(['register.user', 'triggeredBy'])->latest()->paginate(15);
        $enabled = ZReportNotifier::isEnabled();
        $latestRegister = Register::with('user')->orderByRaw('closed_at IS NULL')->latest('id')->first();

        $phones = SmsRecipient::with('addedBy')->orderBy('phone')->get();
        $smsReady = TumaSend::isConfigured();
        // Offer the pharmacy's own phone (System Settings) so adding it is one tap
        $suggestedPhone = $phones->isEmpty() ? TumaSend::normalizePhone(setting('telephone')) : null;

        return view('notifications.index', compact('recipients', 'logs', 'enabled', 'latestRegister', 'phones', 'smsReady', 'suggestedPhone'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAdmin();

        Setting::updateOrCreate(
            ['key' => ZReportNotifier::ENABLED_SETTING],
            ['value' => $request->boolean('enabled') ? '1' : '0']
        );

        return back()->with('success', $request->boolean('enabled')
            ? 'Z report emails are turned on.'
            : 'Z report emails are turned off.');
    }

    public function storeRecipient(Request $request)
    {
        $this->authorizeAdmin();

        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:notification_recipients,email',
            'name' => 'nullable|string|max:255',
        ], [
            'email.unique' => 'This email is already in the list.',
        ]);

        NotificationRecipient::create($validated + ['added_by' => auth()->id()]);

        return back()->with('success', "{$validated['email']} will now receive Z reports.");
    }

    public function toggleRecipient(NotificationRecipient $recipient)
    {
        $this->authorizeAdmin();

        $recipient->update(['is_active' => !$recipient->is_active]);

        return back()->with('success', $recipient->email . ($recipient->is_active ? ' resumed.' : ' paused.'));
    }

    public function destroyRecipient(NotificationRecipient $recipient)
    {
        $this->authorizeAdmin();

        $recipient->delete();

        return back()->with('success', "{$recipient->email} removed.");
    }

    public function storePhone(Request $request)
    {
        $this->authorizeAdmin();

        $request->merge(['phone' => TumaSend::normalizePhone($request->input('phone')) ?? $request->input('phone')]);
        $validated = $request->validate([
            'phone' => ['required', 'regex:/^\+\d{9,15}$/', 'unique:sms_recipients,phone'],
            'name' => 'nullable|string|max:255',
        ], [
            'phone.regex' => 'Enter a valid phone number, e.g. 0991 234 567 or 0881 234 567.',
            'phone.unique' => 'This phone number is already in the list.',
        ]);

        $recipient = SmsRecipient::create($validated + ['added_by' => auth()->id()]);

        return back()->with('success', "{$recipient->display_phone} will now receive Z reports by SMS.");
    }

    public function togglePhone(SmsRecipient $phone)
    {
        $this->authorizeAdmin();

        $phone->update(['is_active' => !$phone->is_active]);

        return back()->with('success', $phone->display_phone . ($phone->is_active ? ' resumed.' : ' paused.'));
    }

    public function destroyPhone(SmsRecipient $phone)
    {
        $this->authorizeAdmin();

        $phone->delete();

        return back()->with('success', "{$phone->display_phone} removed.");
    }

    /**
     * Empty this shop's "Recently Sent" history (the global scope keeps other shops' logs).
     */
    public function clearLogs()
    {
        $this->authorizeAdmin();

        $count = NotificationLog::query()->delete();

        return back()->with('success', $count ? "Cleared {$count} sent " . ($count === 1 ? 'entry' : 'entries') . '.' : 'Nothing to clear.');
    }

    /**
     * Text the latest Z report now to all active phone numbers.
     */
    public function sendTestSms(ZReportNotifier $notifier)
    {
        $this->authorizeAdmin();

        $register = Register::orderByRaw('closed_at IS NULL')->latest('id')->first();
        if (!$register) {
            return back()->with('error', 'There are no registers yet to build a Z report from.');
        }

        $log = $notifier->sendSms($register, auth()->id());
        if (!$log) {
            return back()->with('error', 'Add a phone number first.');
        }

        return $log->status === 'sent'
            ? back()->with('success', 'Z report SMS sent to ' . $log->recipients . '.' . ($log->error ? ' ' . $log->error : ''))
            : back()->with('error', 'SMS failed: ' . $log->error);
    }

    /**
     * Show the Z report email in the browser (latest register, or a chosen one).
     */
    public function preview(Request $request)
    {
        $this->authorizeAdmin();

        $register = $request->filled('register')
            ? Register::findOrFail($request->input('register'))
            : Register::orderByRaw('closed_at IS NULL')->latest('id')->first();

        if (!$register) {
            return back()->with('error', 'There are no registers yet to build a Z report from.');
        }

        return (new ZReportMail((new ZReportService())->build($register)))->render();
    }

    /**
     * Send a Z report now for the latest register (or a chosen one), to all active recipients.
     */
    public function sendTest(Request $request, ZReportNotifier $notifier)
    {
        $this->authorizeAdmin();

        $register = $request->filled('register')
            ? Register::findOrFail($request->input('register'))
            : Register::orderByRaw('closed_at IS NULL')->latest('id')->first();

        if (!$register) {
            return back()->with('error', 'There are no registers yet to build a Z report from.');
        }

        $log = $notifier->send($register, null, auth()->id());

        return $log->status === 'sent'
            ? back()->with('success', 'Z report sent to ' . $log->recipients . '.')
            : back()->with('error', 'Sending failed: ' . $log->error);
    }
}
