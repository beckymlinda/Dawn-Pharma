<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;">
    <tr>
        <td style="padding:24px 28px;border-bottom:1px solid #e2e8f0;">
            <div style="font-size:13px;color:#64748b;">New proof of payment</div>
            <div style="font-size:20px;font-weight:bold;margin-top:4px;">{{ $s->tenant->name }}</div>
            <div style="font-size:13px;color:#64748b;">Shop code: {{ $s->tenant->slug }}</div>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 28px;">
            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;">
                <tr><td style="color:#64748b;width:42%;">Amount</td><td style="font-weight:bold;font-size:18px;">{{ formatCurrency($s->amount) }}</td></tr>
                <tr><td style="color:#64748b;">For</td><td>{{ $s->months }} {{ $s->months == 1 ? 'month' : 'months' }} ({{ $s->tenant->planName() }} plan{{ $s->tenant->monthlyPrice() ? ', ' . formatCurrency($s->tenant->monthlyPrice()) . '/month' : '' }})</td></tr>
                <tr><td style="color:#64748b;">Paid to</td><td>{{ $s->accountLabel() }}</td></tr>
                <tr><td style="color:#64748b;">Transaction reference</td><td>{{ $s->reference ?: '—' }}</td></tr>
                <tr><td style="color:#64748b;">Date paid</td><td>{{ $s->paid_on->format('d M Y') }}</td></tr>
                <tr><td style="color:#64748b;">Submitted by</td><td>{{ $s->submitted_by_name }}@if($s->submitted_by_email) &lt;{{ $s->submitted_by_email }}&gt;@endif</td></tr>
                @if($s->tenant->contact_phone)<tr><td style="color:#64748b;">Shop phone</td><td>{{ $s->tenant->contact_phone }}</td></tr>@endif
                <tr><td style="color:#64748b;">Currently paid until</td><td>{{ $s->tenant->paid_until?->format('d M Y') ?? 'no end date' }}</td></tr>
                @if($s->note)<tr><td style="color:#64748b;">Note</td><td>{{ $s->note }}</td></tr>@endif
            </table>
            <p style="font-size:13px;color:#64748b;margin:16px 0;">The proof ({{ $s->proof_name }}) is attached. Check it against your {{ $s->accountLabel() }} statement before approving.</p>
            <a href="{{ $reviewUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:bold;">Review in the admin portal</a>
        </td>
    </tr>
</table>
</body>
</html>
