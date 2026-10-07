<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ZReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $report)
    {
    }

    public function envelope(): Envelope
    {
        $r = $this->report;

        return new Envelope(
            from: new Address(config('mail.from.address'), $r['business']['name']),
            subject: ($r['is_closed'] ? '' : '[PREVIEW] ') . "Z Report {$r['report_no']} - "
                . ($r['cashier']->name ?? 'Register') . ' - ' . $r['closed_at']->format('d M Y')
                . ' - ' . formatCurrency($r['gross_sales']),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.z-report');
    }

    /**
     * Attach the report as a CSV so it can be opened in Excel for bookkeeping.
     */
    public function attachments(): array
    {
        $r = $this->report;
        $rows = [
            ['Z Report', $r['report_no']],
            ['Business', $r['business']['name']],
            ['Cashier', $r['cashier']->name ?? ''],
            ['Location', $r['location'] ?? ''],
            ['Opened', optional($r['opened_at'])->format('Y-m-d H:i')],
            ['Closed', $r['closed_at']->format('Y-m-d H:i')],
            ['Transactions', $r['sales_count']],
            ['Gross Sales', $r['gross_sales']],
            ['Opening Cash', $r['opening_cash']],
            ['Expected Cash in Drawer', $r['expected_cash']],
            [],
            ['Payment Method', 'Count', 'Amount', 'Of which from split payments'],
        ];
        foreach ($r['payments'] as $p) {
            $rows[] = [$p['label'], $p['count'], $p['amount'], $p['split_amount']];
        }
        $rows[] = [];
        $rows[] = ['Product', 'SKU', 'Qty Sold', 'Amount'];
        foreach ($r['products'] as $p) {
            $rows[] = [$p->name ?? 'Deleted product', $p->sku, (float) $p->qty, (float) $p->amount];
        }
        $rows[] = [];
        $rows[] = ['Time', 'Invoice', 'Customer', 'Payment', 'Amount'];
        foreach ($r['transactions'] as $t) {
            $rows[] = [$t->created_at, $t->invoice_no, $t->customer_name, $t->payment_label, (float) $t->total_amount];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return [
            Attachment::fromData(fn () => $csv, "z-report-{$r['report_no']}.csv")->withMime('text/csv'),
        ];
    }
}
