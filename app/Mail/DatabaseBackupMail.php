<?php

namespace App\Mail;

use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DatabaseBackupMail extends Mailable
{
    public function __construct(public string $file, public float $sizeMb)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Database backup - ' . config('app.name') . ' - ' . now()->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(htmlString:
            '<p>Attached is the automatic database backup for <strong>' . e(config('app.name')) . '</strong>'
            . ' taken on ' . now()->format('d M Y H:i') . ' (' . $this->sizeMb . ' MB).</p>'
            . '<p>Keep this email. To restore, unzip the file and import it in phpMyAdmin.</p>'
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->file)->as(basename($this->file))->withMime('application/gzip'),
        ];
    }
}
