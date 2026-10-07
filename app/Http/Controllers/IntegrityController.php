<?php

namespace App\Http\Controllers;

use App\Models\IntegrityEvent;
use App\Models\Setting;
use App\Models\User;
use App\Services\IntegrityMonitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegrityController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless($user->hasRole('super_admin') || $user->hasRole('admin'), 403);
    }

    /**
     * Loss Prevention dashboard: cashier risk ranking and event feed.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(6)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        $inRange = IntegrityEvent::whereBetween('created_at', [$from, $to]);

        // KPI tiles
        $kpis = [
            'needs_review' => (clone $inRange)->whereIn('severity', ['high', 'critical'])->where('reviewed', false)->count(),
            'deleted_value' => (float) (clone $inRange)->where('type', 'sale_deleted')->sum('amount'),
            'deleted_count' => (clone $inRange)->where('type', 'sale_deleted')->count(),
            'shortage_value' => (float) (clone $inRange)->where('type', 'cash_shortage')->sum('amount'),
            'edit_value' => (float) (clone $inRange)->whereIn('type', ['sale_edited', 'price_override', 'below_cost'])->sum('amount'),
            'void_count' => (clone $inRange)->whereIn('type', ['line_void', 'cart_cleared'])->count(),
        ];

        // Events per user and type
        $byUser = (clone $inRange)->whereNotNull('user_id')
            ->select('user_id', 'type', 'severity', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))
            ->groupBy('user_id', 'type', 'severity')
            ->get()
            ->groupBy('user_id');

        // Sales per user in the same period, to compare like with like
        $salesByUser = DB::table('sales')
            ->leftJoin('registers', 'registers.id', '=', 'sales.register_id')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('COALESCE(sales.user_id, registers.user_id) as uid, COUNT(*) as count, SUM(sales.total_amount) as total')
            ->groupBy('uid')
            ->get()
            ->keyBy('uid');

        $userIds = $byUser->keys()->merge($salesByUser->keys())->filter()->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $cashiers = $userIds->map(function ($id) use ($byUser, $salesByUser, $users) {
            $events = $byUser->get($id, collect());
            $sum = fn (array $types, string $field = 'count') => (float) $events->whereIn('type', $types)->sum($field);
            $score = $events->sum(fn ($e) => IntegrityEvent::WEIGHTS[$e->severity] * $e->count);
            $sales = (int) ($salesByUser[$id]->count ?? 0);

            return (object) [
                'user' => $users[$id] ?? null,
                'sales_count' => $sales,
                'sales_total' => (float) ($salesByUser[$id]->total ?? 0),
                'deleted' => $sum(['sale_deleted']),
                'deleted_value' => $sum(['sale_deleted'], 'amount'),
                'edited' => $sum(['sale_edited']),
                'edited_value' => $sum(['sale_edited'], 'amount'),
                'price_overrides' => $sum(['price_override', 'below_cost']),
                'voids' => $sum(['line_void', 'cart_cleared']),
                'cash_short' => $sum(['cash_shortage'], 'amount'),
                'stock_removed' => $sum(['stock_removed']),
                'other' => $sum(['after_hours_sale', 'sale_without_register', 'delete_blocked', 'cash_not_counted', 'cash_surplus', 'failed_login']),
                'score' => round($score, 1),
                'score_per_100' => $sales > 0 ? round($score / $sales * 100, 1) : null,
            ];
        })->filter(fn ($c) => $c->user)->sortByDesc('score')->values();

        // Flag users whose risk rate is well above the team average
        $rates = $cashiers->pluck('score_per_100')->filter(fn ($r) => $r !== null);
        $avgRate = $rates->count() ? $rates->avg() : 0;
        $cashiers->each(function ($c) use ($avgRate) {
            $c->flag = $c->score >= 10 && ($c->score_per_100 === null || $c->score_per_100 > max($avgRate * 1.5, 1));
        });

        // Event feed
        $events = IntegrityEvent::with('user')
            ->whereBetween('created_at', [$from, $to])
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->input('user')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->input('severity')))
            ->when($request->input('status') === 'open', fn ($q) => $q->where('reviewed', false))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $settings = collect(IntegrityEvent::SETTING_DEFAULTS)->map(fn ($v, $k) => IntegrityEvent::setting($k));
        $allUsers = User::orderBy('name')->get(['id', 'name']);

        return view('integrity.index', compact('kpis', 'cashiers', 'avgRate', 'events', 'from', 'to', 'settings', 'allUsers'));
    }

    public function show(IntegrityEvent $event)
    {
        $this->authorizeAdmin();
        $event->load(['user', 'reviewer', 'register.user', 'product']);

        return view('integrity.show', compact('event'));
    }

    /**
     * Mark an event as reviewed (explained / investigated), with a note.
     */
    public function review(Request $request, IntegrityEvent $event)
    {
        $this->authorizeAdmin();
        $data = $request->validate(['review_note' => 'nullable|string|max:500']);

        $event->update([
            'reviewed' => !$event->reviewed,
            'reviewed_by' => $event->reviewed ? null : auth()->id(),
            'reviewed_at' => $event->reviewed ? null : now(),
            'review_note' => $event->reviewed ? $event->review_note : ($data['review_note'] ?? null),
        ]);

        return back()->with('success', $event->reviewed ? 'Marked as reviewed.' : 'Marked as open again.');
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'lp_open_time' => 'required|date_format:H:i',
            'lp_close_time' => 'required|date_format:H:i',
            'lp_cash_tolerance' => 'required|numeric|min:0',
        ]);
        $data['lp_blind_close'] = $request->boolean('lp_blind_close') ? '1' : '0';

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return back()->with('success', 'Loss prevention settings saved.');
    }

    /**
     * Called by the POS screen when items are removed or the cart is cleared before payment.
     */
    public function reportVoid(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:line_void,cart_cleared',
            'reason' => 'nullable|string|max:100',
            'removed_quantity' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1|max:200',
            'items.*.product_id' => 'nullable|integer',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.quantity' => 'nullable|numeric',
            'items.*.price' => 'nullable|numeric',
        ]);

        $items = collect($data['items'])->map(fn ($i) => $i + ['subtotal' => (float) ($i['price'] ?? 0) * (float) ($i['quantity'] ?? 0)]);

        if ($data['type'] === 'cart_cleared') {
            IntegrityMonitor::recordCartCleared($items->all(), $data['reason'] ?? null);
        } else {
            $item = $items->first();
            IntegrityMonitor::recordCartRemoval($item, (float) ($data['removed_quantity'] ?? $item['quantity'] ?? 0),
                ($data['reason'] ?? '') === 'removed' ? 'removed' : 'reduced');
        }

        return response()->json(['success' => true]);
    }
}
