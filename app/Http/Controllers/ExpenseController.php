<?php

namespace App\Http\Controllers;

use App\Helpers\PaymentMethods;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        // Default: this month
        $from = $this->date($request->input('from'), now()->startOfMonth());
        $to = $this->date($request->input('to'), now()->endOfMonth());
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $query = Expense::with('user')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')));

        $byCategory = (clone $query)->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')->orderByDesc('total')->get();

        $expenses = $query->orderByDesc('expense_date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'byCategory' => $byCategory,
            'total' => $byCategory->sum('total'),
            'from' => $from,
            'to' => $to,
            'categories' => Expense::CATEGORIES,
            // Only methods switched on in Settings → Payment methods
            'paymentMethods' => PaymentMethods::enabledNames(old('payment_method')),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::getDisplayNames()))],
        ]);

        Expense::create($validated + ['user_id' => auth()->id()]);

        return redirect()->back()->with('success', 'Expense of ' . formatCurrency($validated['amount']) . ' recorded.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->back()->with('success', 'Expense deleted.');
    }

    private function date($value, Carbon $default): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value) : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
