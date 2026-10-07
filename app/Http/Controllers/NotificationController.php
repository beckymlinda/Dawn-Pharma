<?php

namespace App\Http\Controllers;

use App\Mail\ZReportMail;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\Register;
use App\Models\Setting;
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

        return view('notifications.index', compact('recipients', 'logs', 'enabled', 'latestRegister'));
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
