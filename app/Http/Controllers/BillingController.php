<?php

namespace App\Http\Controllers;

use App\Services\PaymentProofNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * The shop's subscription: plan, paid-until date, days left, where to pay,
 * uploading proof of payment, and payments made. For the shop's owners and admins
 * (also while the shop is blocked, so they can pay and send proof).
 */
class BillingController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        abort_unless($tenant, 404);

        return view('billing.index', [
            'tenant' => $tenant,
            'payments' => $tenant->payments()->limit(24)->get(),
            'submissions' => $tenant->paymentSubmissions()->limit(10)->get(),
            'accounts' => config('tenancy.payment_accounts'),
            'contact' => config('tenancy.billing_contact'),
        ]);
    }

    public function submit(Request $request)
    {
        $tenant = tenant();
        abort_unless($tenant, 404);

        $data = $request->validate([
            'months' => 'required|integer|in:1,3,6,12',
            'amount' => 'required|numeric|min:1|max:999999999',
            'account' => ['required', Rule::in(array_keys(config('tenancy.payment_accounts')))],
            'reference' => 'nullable|string|max:100',
            'paid_on' => 'required|date|before_or_equal:today|after:' . now()->subDays(90)->toDateString(),
            'note' => 'nullable|string|max:500',
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ], [
            'proof.required' => 'Please attach a screenshot or photo of the payment confirmation.',
            'proof.mimes' => 'The proof must be a picture (JPG, PNG, WEBP) or a PDF.',
            'proof.max' => 'The file is too large (5 MB maximum). A screenshot is usually enough.',
            'paid_on.after' => 'The payment date looks too old. Contact your provider.',
        ]);

        $file = $request->file('proof');
        $path = $file->store("payment-proofs/{$tenant->id}", 'local');

        $submission = $tenant->paymentSubmissions()->create([
            'submitted_by' => $request->user()->id,
            'submitted_by_name' => $request->user()->name,
            'submitted_by_email' => $request->user()->email,
            'months' => $data['months'],
            'amount' => $data['amount'],
            'account' => $data['account'],
            'reference' => $data['reference'] ?? null,
            'paid_on' => $data['paid_on'],
            'proof_path' => $path,
            'proof_name' => substr($file->getClientOriginalName(), 0, 200),
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        // Email the software owner after the page has been sent, so a slow mail server does not keep
        // the shop waiting; retried if the mail server is busy (see PaymentProofNotifier)
        dispatch(fn () => app(PaymentProofNotifier::class)->send($submission))->afterResponse();

        return redirect()->route('billing.index')
            ->with('success', 'Thank you. Your proof of payment was sent and will be checked shortly. Your access is extended as soon as it is approved.');
    }

    /** The shop's own uploaded proof */
    public function proof(int $submission)
    {
        $tenant = tenant();
        $item = $tenant?->paymentSubmissions()->whereKey($submission)->firstOrFail();

        abort_unless($item && Storage::disk('local')->exists($item->proof_path), 404);

        return Storage::disk('local')->response($item->proof_path, $item->proof_name);
    }
}
