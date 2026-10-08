<?php

namespace App\Http\Controllers;

use App\Models\PaymentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin portal: proofs of payment uploaded by shops. Approving records the
 * payment and extends the shop's subscription (and lifts a block); rejecting
 * shows the reason on the shop's Billing page.
 */
class PaymentReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->input('status'), ['pending', 'approved', 'rejected', 'all'], true) ? $request->input('status') : 'pending';

        $submissions = PaymentSubmission::with('tenant')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('platform.payments', [
            'submissions' => $submissions,
            'status' => $status,
            'pendingCount' => PaymentSubmission::where('status', 'pending')->count(),
        ]);
    }

    public function approve(Request $request, PaymentSubmission $submission)
    {
        abort_unless($submission->isPending(), 409, 'This proof has already been reviewed.');

        $data = $request->validate([
            'months' => 'required|integer|min:1|max:24',
            'amount' => 'required|numeric|min:0|max:999999999',
        ]);

        $account = config("tenancy.payment_accounts.{$submission->account}.label", $submission->account);
        $payment = $submission->tenant->recordPayment((int) $data['months'], (float) $data['amount'], $request->user()->name, [
            'method' => $account,
            'reference' => $submission->reference,
            'paid_on' => $submission->paid_on,
        ]);

        $submission->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->name,
            'reviewed_at' => now(),
            'tenant_payment_id' => $payment->id,
        ]);

        return back()->with('success', "Approved. {$submission->tenant->name} is paid until {$payment->period_to->format('d M Y')}.");
    }

    public function reject(Request $request, PaymentSubmission $submission)
    {
        abort_unless($submission->isPending(), 409, 'This proof has already been reviewed.');

        $data = $request->validate(['review_note' => 'required|string|max:500'], [
            'review_note.required' => 'Tell the shop why, so they can fix it (e.g. "Amount not received on Airtel Money").',
        ]);

        $submission->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->name,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        return back()->with('success', "Rejected. {$submission->tenant->name} will see your reason on their Billing page.");
    }

    /** Send the "new proof of payment" email again (e.g. after the mail server refused it) */
    public function resendEmail(PaymentSubmission $submission, \App\Services\PaymentProofNotifier $notifier)
    {
        return $notifier->send($submission, retry: false)
            ? back()->with('success', 'Email sent to ' . config('tenancy.payment_notify_email') . '.')
            : back()->with('error', 'The email could not be sent: ' . $submission->fresh()->notify_error);
    }

    public function proof(PaymentSubmission $submission)
    {
        abort_unless(Storage::disk('local')->exists($submission->proof_path), 404);

        return Storage::disk('local')->response($submission->proof_path, $submission->proof_name);
    }
}
