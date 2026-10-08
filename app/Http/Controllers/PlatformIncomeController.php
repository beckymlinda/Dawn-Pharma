<?php

namespace App\Http\Controllers;

use App\Models\PaymentSubmission;
use App\Models\Tenant;
use App\Models\TenantPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Admin portal: the software owner's income from each pharmacy, month by month,
 * plus proofs of payment still waiting for approval.
 */
class PlatformIncomeController extends Controller
{
    public function index(Request $request)
    {
        $firstYear = (int) (TenantPayment::min('paid_on') ? Carbon::parse(TenantPayment::min('paid_on'))->year : now()->year);
        $year = (int) $request->input('year', now()->year);
        $year = max($firstYear, min($year, now()->year));

        $payments = TenantPayment::whereBetween('paid_on', ["{$year}-01-01", "{$year}-12-31"])->get();

        // [tenant_id][month 1..12] => amount
        $grid = [];
        foreach ($payments as $payment) {
            $grid[$payment->tenant_id][$payment->paid_on->month] = ($grid[$payment->tenant_id][$payment->paid_on->month] ?? 0) + (float) $payment->amount;
        }

        $pending = PaymentSubmission::where('status', 'pending')
            ->selectRaw('tenant_id, SUM(amount) as total, COUNT(*) as n')->groupBy('tenant_id')->get()->keyBy('tenant_id');

        $tenants = Tenant::orderBy('name')->get()->map(fn (Tenant $t) => [
            'tenant' => $t,
            'months' => $grid[$t->id] ?? [],
            'total' => array_sum($grid[$t->id] ?? []),
            'pending' => (float) ($pending[$t->id]->total ?? 0),
            'pending_count' => (int) ($pending[$t->id]->n ?? 0),
        ])->sortByDesc('total')->values();

        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = $tenants->sum(fn ($row) => $row['months'][$m] ?? 0);
        }

        // Individual payments of one month (defaults to the latest month with income)
        $lastMonthWithIncome = collect($monthTotals)->filter()->keys()->last();
        $month = (int) $request->input('month', $year === now()->year ? now()->month : ($lastMonthWithIncome ?? 12));
        $month = max(1, min(12, $month));
        $monthPayments = $payments->filter(fn ($p) => $p->paid_on->month === $month)
            ->sortByDesc('paid_on')->load('tenant');

        return view('platform.income', [
            'year' => $year,
            'years' => range(now()->year, $firstYear),
            'month' => $month,
            'tenants' => $tenants,
            'monthTotals' => $monthTotals,
            'yearTotal' => array_sum($monthTotals),
            'pendingTotal' => (float) $pending->sum('total'),
            'monthPayments' => $monthPayments,
            // Average over the months since the first income of the year (not the empty months before)
            'monthsCounted' => ($firstIncome = collect($monthTotals)->filter()->keys()->first())
                ? ($year === now()->year ? now()->month : 12) - $firstIncome + 1
                : 0,
        ]);
    }
}
