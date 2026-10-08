<?php

namespace App\Mail;

use App\Models\PaymentSubmission;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

/** Sent to the software owner when a shop uploads proof of payment */
class PaymentProofSubmitted extends Mailable
{
    public function __construct(public PaymentSubmission $submission)
    {
    }

    public function envelope(): Envelope
    {
        $s = $this->submission;

        return new Envelope(
            subject: "Payment proof: {$s->tenant->name} – " . formatCurrency($s->amount) . " ({$s->months} " . ($s->months == 1 ? 'month' : 'months') . ')',
            replyTo: $s->submitted_by_email ? [$s->submitted_by_email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-proof', with: [
            's' => $this->submission,
            'reviewUrl' => url('/platform/payments'),
        ]);
    }

    public function attachments(): array
    {
        $disk = Storage::disk('local');
        $path = $this->submission->proof_path;

        // Attach the proof unless it is very large (most mail servers reject big messages)
        if ($disk->exists($path) && $disk->size($path) <= 8 * 1024 * 1024) {
            return [Attachment::fromStorageDisk('local', $path)->as($this->submission->proof_name ?: basename($path))];
        }

        return [];
    }
}
