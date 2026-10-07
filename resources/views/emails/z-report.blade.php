@php
    $r = $report;
    $accent = $r['business']['primary'];
    $accent2 = $r['business']['secondary'];
    // Embed the logo in the email itself (works offline / on local servers); fall back to the URL in the browser preview
    $logoSrc = null;
    if ($r['business']['logo_path']) {
        $logoSrc = isset($message) ? $message->embed($r['business']['logo_path']) : $r['business']['logo_url'];
    }
    $cell = 'padding:8px 12px;border-bottom:1px solid #eef0f3;font-size:13px;color:#333;';
    $head = 'padding:8px 12px;background:#f6f7f9;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;text-align:left;';
    $section = 'margin:0 0 10px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:' . $accent . ';';
    $txLimit = 100;
    // Phone as an explicit white link, so Gmail / Apple Mail don't auto-link it in blue
    $phone = $r['business']['telephone']
        ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $r['business']['telephone'])) . '" style="color:#ffffff !important;text-decoration:none;">' . e($r['business']['telephone']) . '</a>'
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, address=no, email=no, date=no">
    <title>Z Report {{ $r['report_no'] }}</title>
    <style>
        /* Stop mail apps from recolouring auto-detected phone numbers, addresses and dates */
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; }
        u + #body a { color: inherit; text-decoration: none; }
        #MessageViewBody a { color: inherit; text-decoration: none; }
    </style>
</head>
<body id="body" style="margin:0;padding:0;background:#eef1f5;font-family:Segoe UI,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f5;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);">

    <!-- Header -->
    <tr>
        <td bgcolor="{{ $accent }}" style="background-color:{{ $accent }};background-image:linear-gradient(135deg, {{ $accent }} 0%, {{ $accent2 }} 100%);padding:24px 28px;color:#ffffff;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    @if($logoSrc)
                        <td width="72" style="vertical-align:middle;padding-right:14px;">
                            <div style="background:#ffffff;border-radius:10px;padding:6px;width:56px;text-align:center;">
                                <img src="{{ $logoSrc }}" alt="{{ $r['business']['name'] }}" width="56" style="display:block;width:56px;max-height:56px;height:auto;border:0;">
                            </div>
                        </td>
                    @endif
                    <td style="vertical-align:middle;">
                        <div style="font-size:20px;font-weight:700;color:#ffffff;">{{ $r['business']['name'] }}</div>
                        <div style="font-size:12px;color:#ffffff;opacity:.9;">
                            {{ $r['business']['location'] }}@if($r['business']['location'] && $phone) &middot; @endif{!! $phone !!}
                        </div>
                    </td>
                    <td align="right" style="vertical-align:middle;">
                        <div style="font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:.85;">{{ $r['is_closed'] ? 'End of Shift' : 'Preview (register still open)' }}</div>
                        <div style="font-size:24px;font-weight:800;letter-spacing:1px;">Z REPORT</div>
                        <div style="font-size:13px;font-weight:600;">{{ $r['report_no'] }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Shift details -->
    <tr>
        <td style="padding:20px 28px 4px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;color:#333;">
                <tr>
                    <td style="padding:4px 0;color:#6b7280;width:120px;">Cashier</td>
                    <td style="padding:4px 0;font-weight:600;">{{ $r['cashier']->name ?? 'Unknown' }} <span style="color:#6b7280;font-weight:400;">({{ $r['cashier']->email ?? '' }})</span></td>
                </tr>
                @if($r['location'])
                <tr>
                    <td style="padding:4px 0;color:#6b7280;">Location</td>
                    <td style="padding:4px 0;font-weight:600;">{{ $r['location'] }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding:4px 0;color:#6b7280;">Opened</td>
                    <td style="padding:4px 0;font-weight:600;">{{ optional($r['opened_at'])->format('D, d M Y H:i') }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#6b7280;">{{ $r['is_closed'] ? 'Closed' : 'As at' }}</td>
                    <td style="padding:4px 0;font-weight:600;">{{ $r['closed_at']->format('D, d M Y H:i') }}
                        @if($r['duration']) <span style="color:#6b7280;font-weight:400;">&middot; shift length {{ $r['duration'] }}</span> @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Key figures -->
    <tr>
        <td style="padding:16px 22px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    @foreach([
                        ['Gross Sales', formatCurrency($r['gross_sales'])],
                        ['Transactions', number_format($r['sales_count'])],
                        ['Items Sold', number_format($r['items_sold'])],
                        ['Average Sale', formatCurrency($r['average_sale'])],
                    ] as [$label, $value])
                        <td width="25%" style="padding:6px;">
                            <div style="background:#f6f7f9;border-radius:8px;border-top:3px solid {{ $accent2 }};padding:12px;text-align:center;">
                                <div style="font-size:10px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">{{ $label }}</div>
                                <div style="font-size:16px;font-weight:700;color:{{ $accent }};margin-top:4px;">{{ $value }}</div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>

    <!-- Payment breakdown -->
    <tr>
        <td style="padding:16px 28px 4px;">
            <p style="{{ $section }}">Payments Received</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f3;border-radius:6px;border-collapse:separate;">
                <tr>
                    <th style="{{ $head }}">Method</th>
                    <th style="{{ $head }}text-align:right;">Count</th>
                    <th style="{{ $head }}text-align:right;">Amount</th>
                </tr>
                @forelse($r['payments'] as $p)
                    <tr>
                        <td style="{{ $cell }}">
                            {{ $p['label'] }}
                            @if($p['split_amount'] > 0)
                                <br><span style="font-size:11px;color:#6b7280;">incl. {{ formatCurrency($p['split_amount']) }} from {{ $p['split_count'] }} split payment{{ $p['split_count'] > 1 ? 's' : '' }}</span>
                            @endif
                        </td>
                        <td style="{{ $cell }}text-align:right;">{{ number_format($p['count']) }}</td>
                        <td style="{{ $cell }}text-align:right;">{{ formatCurrency($p['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="{{ $cell }}text-align:center;color:#9ca3af;">No payments recorded</td></tr>
                @endforelse
                <tr>
                    <td style="{{ $cell }}font-weight:700;border-bottom:0;">Total</td>
                    <td style="{{ $cell }}border-bottom:0;"></td>
                    <td style="{{ $cell }}text-align:right;font-weight:700;border-bottom:0;">{{ formatCurrency(collect($r['payments'])->sum('amount')) }}</td>
                </tr>
            </table>
            @if($r['total_due'] > 0)
                <p style="font-size:12px;color:#b45309;margin:8px 0 0;">Outstanding (unpaid) balance on sales: <strong>{{ formatCurrency($r['total_due']) }}</strong></p>
            @endif
        </td>
    </tr>

    <!-- Split payments: how each one was divided -->
    @if($r['split_sales']->isNotEmpty())
    <tr>
        <td style="padding:16px 28px 4px;">
            <p style="{{ $section }}">Split Payments ({{ $r['split_sales']->count() }})</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f3;border-radius:6px;border-collapse:separate;">
                <tr>
                    <th style="{{ $head }}">Time</th>
                    <th style="{{ $head }}">Invoice</th>
                    <th style="{{ $head }}">Paid with</th>
                    <th style="{{ $head }}text-align:right;">Total</th>
                </tr>
                @foreach($r['split_sales'] as $t)
                    <tr>
                        <td style="{{ $cell }}white-space:nowrap;vertical-align:top;">{{ \Carbon\Carbon::parse($t->created_at)->format('H:i') }}</td>
                        <td style="{{ $cell }}vertical-align:top;">{{ $t->invoice_no }}</td>
                        <td style="{{ $cell }}vertical-align:top;">
                            @forelse($t->split as $part)
                                <div>{{ $part['label'] }} <strong>{{ formatCurrency($part['amount']) }}</strong></div>
                            @empty
                                <span style="color:#9ca3af;">No breakdown recorded</span>
                            @endforelse
                        </td>
                        <td style="{{ $cell }}text-align:right;vertical-align:top;font-weight:700;">{{ formatCurrency($t->total_amount) }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
    @endif

    <!-- Cash drawer & profit -->
    <tr>
        <td style="padding:16px 22px 4px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" style="padding:6px;vertical-align:top;">
                        <p style="{{ $section }}">Cash Drawer</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f3;border-radius:6px;border-collapse:separate;">
                            <tr><td style="{{ $cell }}">Opening cash</td><td style="{{ $cell }}text-align:right;">{{ formatCurrency($r['opening_cash']) }}</td></tr>
                            <tr><td style="{{ $cell }}">+ Cash sales</td><td style="{{ $cell }}text-align:right;">{{ formatCurrency($r['cash_sales']) }}</td></tr>
                            @if($r['counted_cash'] !== null)
                                @php $v = $r['cash_variance']; @endphp
                                <tr><td style="{{ $cell }}font-weight:700;">Expected in drawer</td><td style="{{ $cell }}text-align:right;font-weight:700;">{{ formatCurrency($r['expected_cash']) }}</td></tr>
                                <tr><td style="{{ $cell }}">Counted by cashier</td><td style="{{ $cell }}text-align:right;">{{ formatCurrency($r['counted_cash']) }}</td></tr>
                                <tr>
                                    <td style="{{ $cell }}font-weight:700;border-bottom:0;">{{ $v < 0 ? 'Short' : ($v > 0 ? 'Over' : 'Difference') }}</td>
                                    <td style="{{ $cell }}text-align:right;font-weight:700;border-bottom:0;color:{{ abs($v) < 0.01 ? '#15803d' : ($v < 0 ? '#b91c1c' : '#b45309') }};">
                                        {{ abs($v) < 0.01 ? 'Balanced' : ($v < 0 ? '−' : '+') . formatCurrency(abs($v)) }}
                                    </td>
                                </tr>
                            @else
                                <tr><td style="{{ $cell }}font-weight:700;border-bottom:0;">Expected in drawer</td><td style="{{ $cell }}text-align:right;font-weight:700;border-bottom:0;">{{ formatCurrency($r['expected_cash']) }}</td></tr>
                            @endif
                        </table>
                    </td>
                    <td width="50%" style="padding:6px;vertical-align:top;">
                        <p style="{{ $section }}">Profit</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f3;border-radius:6px;border-collapse:separate;">
                            <tr><td style="{{ $cell }}">Gross sales</td><td style="{{ $cell }}text-align:right;">{{ formatCurrency($r['gross_sales']) }}</td></tr>
                            <tr><td style="{{ $cell }}">&minus; Cost of sales</td><td style="{{ $cell }}text-align:right;">{{ formatCurrency($r['cost_of_sales']) }}</td></tr>
                            <tr><td style="{{ $cell }}font-weight:700;border-bottom:0;">Gross profit</td><td style="{{ $cell }}text-align:right;font-weight:700;border-bottom:0;color:{{ $r['gross_profit'] >= 0 ? '#15803d' : '#b91c1c' }};">{{ formatCurrency($r['gross_profit']) }}</td></tr>
                        </table>
                        @if($r['profit_incomplete'])
                            <p style="font-size:11px;color:#9ca3af;margin:6px 0 0;">Some items had no recorded cost, so profit may be overstated.</p>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Integrity alerts for this shift -->
    @php
        $alerts = $r['integrity_events']->whereIn('severity', ['medium', 'high', 'critical']);
        $lowCount = $r['integrity_events']->where('severity', 'low')->count();
        $sevColor = ['medium' => '#0369a1', 'high' => '#b45309', 'critical' => '#b91c1c'];
    @endphp
    @if($r['integrity_events']->isNotEmpty())
    <tr>
        <td style="padding:16px 28px 4px;">
            <p style="{{ $section }}">Integrity Alerts</p>
            @if($alerts->isNotEmpty())
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #fde2e2;border-radius:6px;border-collapse:separate;background:#fffafa;">
                    @foreach($alerts as $a)
                        <tr>
                            <td style="{{ $cell }}white-space:nowrap;vertical-align:top;width:52px;">{{ $a->created_at->format('H:i') }}</td>
                            <td style="{{ $cell }}vertical-align:top;">
                                <span style="display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;color:#fff;background:{{ $sevColor[$a->severity] }};border-radius:3px;padding:1px 6px;margin-right:4px;">{{ $a->severity }}</span>
                                <strong>{{ $a->label }}</strong><br>
                                <span style="color:#555;">{{ $a->description }}</span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
            @if($lowCount)
                <p style="font-size:12px;color:#6b7280;margin:8px 0 0;">{{ $lowCount }} item(s) were removed from carts before payment during this shift.</p>
            @endif
            <p style="font-size:11px;color:#9ca3af;margin:6px 0 0;">Review these under Loss Prevention in the system.</p>
        </td>
    </tr>
    @endif

    <!-- Products sold -->
    <tr>
        <td style="padding:16px 28px 4px;">
            <p style="{{ $section }}">Products Sold</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f3;border-radius:6px;border-collapse:separate;">
                <tr>
                    <th style="{{ $head }}">Product</th>
                    <th style="{{ $head }}text-align:right;">Qty</th>
                    <th style="{{ $head }}text-align:right;">Amount</th>
                </tr>
                @forelse($r['products'] as $p)
                    <tr>
                        <td style="{{ $cell }}">{{ $p->name ?? 'Deleted product' }}@if($p->sku)<br><span style="font-size:11px;color:#9ca3af;">{{ $p->sku }}</span>@endif</td>
                        <td style="{{ $cell }}text-align:right;">{{ number_format($p->qty) }}</td>
                        <td style="{{ $cell }}text-align:right;">{{ formatCurrency($p->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="{{ $cell }}text-align:center;color:#9ca3af;">No products sold</td></tr>
                @endforelse
            </table>
        </td>
    </tr>

    <!-- Transactions -->
    <tr>
        <td style="padding:16px 28px 4px;">
            <p style="{{ $section }}">Transactions</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eef0f3;border-radius:6px;border-collapse:separate;">
                <tr>
                    <th style="{{ $head }}">Time</th>
                    <th style="{{ $head }}">Invoice</th>
                    <th style="{{ $head }}">Customer</th>
                    <th style="{{ $head }}">Payment</th>
                    <th style="{{ $head }}text-align:right;">Amount</th>
                </tr>
                @forelse($r['transactions']->take($txLimit) as $t)
                    <tr>
                        <td style="{{ $cell }}white-space:nowrap;">{{ \Carbon\Carbon::parse($t->created_at)->format('H:i') }}</td>
                        <td style="{{ $cell }}">{{ $t->invoice_no }}@if($t->is_edited) <span style="font-size:10px;color:#b45309;">(edited)</span>@endif</td>
                        <td style="{{ $cell }}">{{ $t->customer_name ?: 'Walk-In Customer' }}</td>
                        <td style="{{ $cell }}">
                            @if($t->split)
                                Split
                                @foreach($t->split as $part)
                                    <br><span style="font-size:11px;color:#6b7280;white-space:nowrap;">{{ $part['label'] }} {{ formatCurrency($part['amount']) }}</span>
                                @endforeach
                            @else
                                {{ $t->payment_label }}
                            @endif
                        </td>
                        <td style="{{ $cell }}text-align:right;">{{ formatCurrency($t->total_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="{{ $cell }}text-align:center;color:#9ca3af;">No transactions during this shift</td></tr>
                @endforelse
            </table>
            @if($r['transactions']->count() > $txLimit)
                <p style="font-size:12px;color:#6b7280;margin:8px 0 0;">Showing the first {{ $txLimit }} of {{ $r['transactions']->count() }} transactions. The full list is in the attached CSV.</p>
            @endif
            @if($r['edited_count'] > 0)
                <p style="font-size:12px;color:#b45309;margin:8px 0 0;">{{ $r['edited_count'] }} sale(s) in this shift were edited after being made.</p>
            @endif
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="padding:24px 28px;font-size:11px;color:#9ca3af;text-align:center;border-top:1px solid #eef0f3;">
            @if($r['is_closed'])
                Generated on {{ $r['generated_at']->format('d M Y H:i') }} for the register closed at {{ $r['closed_at']->format('H:i') }}.<br>
            @else
                Preview generated on {{ $r['generated_at']->format('d M Y H:i') }}. The register is still open, so figures may change.<br>
            @endif
            A CSV copy of this report is attached. Manage who receives these emails under Notifications.
        </td>
    </tr>
    <tr>
        <td bgcolor="{{ $accent }}" style="background-color:{{ $accent }};padding:12px 28px;text-align:center;font-size:12px;font-weight:600;color:#ffffff;">
            {{ $r['business']['name'] }}@if($phone) &middot; {!! $phone !!}@endif
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
