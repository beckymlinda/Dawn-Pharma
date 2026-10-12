<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PaymentSubmission;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\TenantProvisioner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin portal for the software owner: income, shops, their subscriptions and usage.
 */
class PlatformController extends Controller
{
    /** Dashboard: your income and what needs attention */
    public function index()
    {
        $tenants = Tenant::orderBy('name')->get();
        $paidIn = fn (Carbon $from, Carbon $to) => (float) TenantPayment::whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->sum('amount');
        $pending = PaymentSubmission::where('status', 'pending');

        return view('platform.dashboard', [
            'income' => [
                'this_month' => $paidIn(now()->startOfMonth(), now()->endOfMonth()),
                'last_month' => $paidIn(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()),
                'this_year' => $paidIn(now()->startOfYear(), now()->endOfYear()),
                'payments_this_month' => TenantPayment::where('paid_on', '>=', now()->startOfMonth()->toDateString())->count(),
                // What the shops on a plan would bring in each month
                'expected_monthly' => $tenants->filter(fn (Tenant $t) => $t->canBeUsed() && $t->plan)->sum(fn (Tenant $t) => $t->monthlyPrice()),
            ],
            'pending' => ['count' => (clone $pending)->count(), 'amount' => (float) (clone $pending)->sum('amount')],
            'shops' => [
                'total' => $tenants->count(),
                'working' => $tenants->filter->canBeUsed()->count(),
                'due_soon' => $tenants->filter(fn ($t) => $t->billingState() === 'due_soon')->count(),
                'overdue' => $tenants->filter(fn ($t) => in_array($t->billingState(), ['grace', 'expired']))->count(),
                'suspended' => $tenants->filter->isSuspended()->count(),
            ],
            'attention' => $tenants->filter(fn ($t) => in_array($t->billingState(), ['due_soon', 'grace', 'expired']))
                ->sortBy(fn ($t) => $t->daysLeft() ?? PHP_INT_MAX)->take(8),
            'recentPayments' => TenantPayment::with('tenant')->orderByDesc('paid_on')->orderByDesc('id')->take(8)->get(),
        ]);
    }

    /** All shops with their subscription and what they have paid you */
    public function tenants(Request $request)
    {
        $tenants = Tenant::orderBy('name')->get();
        $filter = $request->input('show', 'all');
        $listed = $tenants->filter(fn (Tenant $t) => match ($filter) {
            'attention' => in_array($t->billingState(), ['due_soon', 'grace', 'expired']),
            'suspended' => $t->isSuspended(),
            default => true,
        });

        return view('platform.tenants', [
            'tenants' => $listed,
            'usage' => $this->usage(),
            'filter' => $filter,
            'counts' => [
                'all' => $tenants->count(),
                'attention' => $tenants->filter(fn ($t) => in_array($t->billingState(), ['due_soon', 'grace', 'expired']))->count(),
                'suspended' => $tenants->filter->isSuspended()->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('platform.create', ['plans' => config('tenancy.plans')]);
    }

    public function store(Request $request, TenantProvisioner $provisioner)
    {
        $data = $request->validate($this->shopRules() + [
            'slug' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn($this->reservedSlugs()), Rule::unique('tenants', 'slug')],
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255',
            'owner_password' => 'required|string|min:8|confirmed',
        ], [
            'slug.regex' => 'Use lowercase letters, numbers and dashes only (e.g. area25 or cnc-health).',
            'slug.not_in' => 'That shop code is used by the system. Choose another one.',
        ]);

        $tenant = $provisioner->create(
            collect($data)->only(['name', 'slug', 'plan', 'paid_until', 'grace_days', 'contact_name', 'contact_phone', 'contact_email', 'notes'])->all(),
            ['name' => $data['owner_name'], 'email' => $data['owner_email'], 'password' => $data['owner_password']]
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('success', "Shop created. Its staff log in at {$tenant->url()}");
    }

    public function show(Tenant $tenant)
    {
        $owners = User::withoutTenantScope()->where('tenant_id', $tenant->id)
            ->whereHas('roles', fn ($q) => $q->withoutGlobalScope('tenant')->where('is_superadmin', true))
            ->get();

        return view('platform.show', [
            'tenant' => $tenant,
            'payments' => $tenant->payments()->limit(24)->get(),
            'usage' => $this->usage($tenant->id)[$tenant->id] ?? $this->emptyUsage(),
            'owners' => $owners,
            'plans' => config('tenancy.plans'),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $tenant->update($request->validate($this->shopRules()));

        return back()->with('success', 'Shop details saved.');
    }

    /** Record a payment: saved in the shop's payment history and moves paid-until forward */
    public function extend(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'months' => 'required|integer|in:1,3,6,12',
            'amount' => 'nullable|numeric|min:0|max:999999999',
            'method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
        ]);
        $payment = $tenant->recordPayment((int) $data['months'], isset($data['amount']) ? (float) $data['amount'] : null,
            $request->user()->name, ['method' => $data['method'] ?? null, 'reference' => $data['reference'] ?? null]);

        return back()->with('success', 'Payment recorded. Paid until ' . $payment->period_to->format('d M Y') . '.');
    }

    public function suspend(Request $request, Tenant $tenant)
    {
        $reason = $request->validate(['reason' => 'nullable|string|max:255'])['reason'] ?? null;
        $tenant->update(['status' => 'suspended', 'suspended_reason' => $reason]);

        return back()->with('success', "{$tenant->name} is suspended. Its staff can no longer use the system.");
    }

    public function reactivate(Tenant $tenant)
    {
        $tenant->update(['status' => 'active', 'suspended_reason' => null]);

        return back()->with('success', "{$tenant->name} is active again.");
    }

    /** Set a new password for one of the shop's accounts (e.g. the owner forgot theirs) */
    public function updatePassword(Request $request, Tenant $tenant, int $userId)
    {
        $user = User::withoutTenantScope()->where('tenant_id', $tenant->id)->findOrFail($userId);
        $data = $request->validateWithBag("password_{$user->id}", [
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->forceFill([
            'password' => $data['password'],
            // "Remember me" logins made with the old password stop working
            'remember_token' => Str::random(60),
        ])->save();

        $by = $request->user()->name . ' (' . $request->user()->email . ')';
        with_tenant($tenant, fn () => ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'password_reset',
            'description' => "Password for {$user->name} changed from the admin portal by {$by}",
            'ip_address' => $request->ip(),
        ]));

        return back()->with('success', "Password changed for {$user->name}. Give them the new password to log in.");
    }

    /** Slugs a shop cannot have: the reserved list plus the first part of every page address */
    private function reservedSlugs(): array
    {
        $fromRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->map(fn ($route) => strtolower(explode('/', $route->uri())[0]))
            ->filter(fn ($segment) => $segment !== '' && !str_starts_with($segment, '{'));

        return $fromRoutes->merge(config('tenancy.reserved_slugs'))->unique()->values()->all();
    }

    private function shopRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'plan' => ['nullable', Rule::in(array_keys(config('tenancy.plans')))],
            'paid_until' => 'nullable|date',
            'grace_days' => 'required|integer|min:0|max:90',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Per shop: staff, products, when it was last used, and what it has paid you
     * (this month and in total). The shops' own sales figures are not shown here.
     */
    private function usage(?int $tenantId = null): array
    {
        $scope = fn ($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q->whereNotNull('tenant_id');
        $count = fn (string $table) => $scope(DB::table($table))->groupBy('tenant_id')->selectRaw('tenant_id, COUNT(*) as n')->pluck('n', 'tenant_id');

        $users = $count('users');
        $products = $count('products');
        $lastUsed = $scope(DB::table('sales'))->groupBy('tenant_id')->selectRaw('tenant_id, MAX(created_at) as at')->pluck('at', 'tenant_id');
        $paidMonth = $scope(DB::table('tenant_payments'))->where('paid_on', '>=', now()->startOfMonth()->toDateString())
            ->groupBy('tenant_id')->selectRaw('tenant_id, SUM(amount) as total')->pluck('total', 'tenant_id');
        $paidTotal = $scope(DB::table('tenant_payments'))->groupBy('tenant_id')->selectRaw('tenant_id, SUM(amount) as total')->pluck('total', 'tenant_id');
        $pending = $scope(DB::table('payment_submissions'))->where('status', 'pending')
            ->groupBy('tenant_id')->selectRaw('tenant_id, SUM(amount) as total')->pluck('total', 'tenant_id');

        $ids = $tenantId ? [$tenantId] : Tenant::pluck('id')->all();
        $result = [];
        foreach ($ids as $id) {
            $result[$id] = [
                'users' => (int) ($users[$id] ?? 0),
                'products' => (int) ($products[$id] ?? 0),
                'last_used' => isset($lastUsed[$id]) ? Carbon::parse($lastUsed[$id]) : null,
                'paid_month' => (float) ($paidMonth[$id] ?? 0),
                'paid_total' => (float) ($paidTotal[$id] ?? 0),
                'pending' => (float) ($pending[$id] ?? 0),
            ];
        }

        return $result;
    }

    private function emptyUsage(): array
    {
        return ['users' => 0, 'products' => 0, 'last_used' => null, 'paid_month' => 0, 'paid_total' => 0, 'pending' => 0];
    }
}
