<?php

namespace App\Http\Controllers;

use App\Models\Register;
use App\Services\IntegrityMonitor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Show register open form
     */
    public function create()
    {
        $openRegister = Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        return view('pos.register.open', [
            'openRegister' => $openRegister,
        ]);
    }

    /**
     * Open a new register session
     */
    public function store(Request $request)
    {
        // Check if user already has open register
        $existingRegister = Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if ($existingRegister) {
            return back()->with('error', 'Register already open. Please close it first.');
        }

        // Validate opening cash
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        // Create new register
        Register::create([
            'user_id' => auth()->id(),
            'location_id' => $validated['location_id'] ?? null,
            'opening_cash' => $validated['opening_cash'],
            'opened_at' => now(),
        ]);

        return redirect()->route('pos.new')
            ->with('success', 'Register opened successfully with MWK ' . number_format($validated['opening_cash'], 2));
    }

    /**
     * Show register close form
     */
    public function confirm()
    {
        $openRegister = Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if (!$openRegister) {
            return back()->with('error', 'No open register to close.');
        }

        $totals = [
            'total_cash' => 0, 'total_card' => 0, 'total_cheques' => 0,
            'total_bank_transfer' => 0, 'total_advance' => 0, 'total_standard_bank' => 0,
            'total_national_bank' => 0, 'total_mpamba' => 0, 'total_airtel_money' => 0,
            'total_cash_and_card' => 0, 'total_sales' => 0,
        ];

        $counts = [
            'count_cash' => 0, 'count_card' => 0, 'count_cheques' => 0,
            'count_bank_transfer' => 0, 'count_advance' => 0, 'count_standard_bank' => 0,
            'count_national_bank' => 0, 'count_mpamba' => 0, 'count_airtel_money' => 0,
            'count_cash_and_card' => 0, 'count_total_sales' => 0,
        ];

        // Get overall stats (Total Sales count and sum) in one fast query
        $overallStats = \Illuminate\Support\Facades\DB::table('sales')
            ->where('register_id', $openRegister->id)
            ->select(
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), 
                \Illuminate\Support\Facades\DB::raw('SUM(total_amount) as total')
            )
            ->first();

        $totals['total_sales'] = (float)($overallStats->total ?? 0);
        $counts['count_total_sales'] = (int)($overallStats->count ?? 0);

        // Get totals and counts for non-split sales (group by payment_method)
        $simpleSales = \Illuminate\Support\Facades\DB::table('sales')
            ->where('register_id', $openRegister->id)
            ->where('payment_method', '!=', 'split')
            ->select(
                'payment_method', 
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), 
                \Illuminate\Support\Facades\DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('payment_method')
            ->get();

        // Get totals and counts for split payments
        $splitPayments = \Illuminate\Support\Facades\DB::table('payments')
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->where('sales.register_id', $openRegister->id)
            ->where('sales.payment_method', '=', 'split')
            ->select(
                'payments.method', 
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), 
                \Illuminate\Support\Facades\DB::raw('SUM(payments.amount) as total')
            )
            ->groupBy('payments.method')
            ->get();

        // Process simple sales
        foreach ($simpleSales as $item) {
            $method = strtolower($item->payment_method);
            $methodKey = 'total_' . ($method === 'cheque' ? 'cheques' : $method);
            $countKey = 'count_' . ($method === 'cheque' ? 'cheques' : $method);

            if (array_key_exists($methodKey, $totals)) {
                $totals[$methodKey] = (float)$item->total;
            }
            if (array_key_exists($countKey, $counts)) {
                $counts[$countKey] = (int)$item->count;
            }
        }

        // Process split payments
        foreach ($splitPayments as $item) {
            $method = strtolower($item->method);
            $methodKey = 'total_' . ($method === 'cheque' ? 'cheques' : $method);
            $countKey = 'count_' . ($method === 'cheque' ? 'cheques' : $method);

            if (array_key_exists($methodKey, $totals)) {
                $totals[$methodKey] += (float)$item->total;
            }
            if (array_key_exists($countKey, $counts)) {
                $counts[$countKey] += (int)$item->count;
            }
        }

        return view('pos.register.close', [
            'register' => $openRegister,
            'totals' => $totals,
            'counts' => $counts,
        ]);
    }

    /**
     * Finalize and close register session
     * Uses PATCH method to avoid WAF blocking 'close' keyword
     */
    public function finalize(Request $request, Register $register)
    {
        // Verify register belongs to user and is open
        if ($register->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if ($register->closed_at) {
            return back()->with('error', 'Register is already closed.');
        }

        $request->validate(['counted_cash' => 'nullable|numeric|min:0']);

        // Closing cash is total of all transactions
        $closingCash = $register->sales()->sum('total_amount');

        // Update register status to closed
        $register->update([
            'closing_cash' => $closingCash,
            'closed_at' => now(),
        ] + IntegrityMonitor::closeRegister($register, $request->input('counted_cash')));

        return redirect()->route('dashboard')
            ->with('success', 'Register closed successfully. Total: MWK ' . number_format($closingCash, 2));
    }

    /**
     * Generic status update method
     * Handles register status updates via request body (action-based design)
     * This method is WAF-safe as it doesn't use sensitive keywords in the URL
     */
    public function updateStatus(Request $request, Register $register)
    {
        // Verify register belongs to user
        if ((int)$register->user_id !== (int)auth()->id()) {
    abort(403, 'Unauthorized');
}

        // Validate the action being requested
        $validated = $request->validate([
            'action' => 'required|string|in:finalize,reopen',
        ]);

        $action = $validated['action'];

        if ($action === 'finalize') {
            // Handle register finalization
            if ($register->closed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Register is already closed.'
                ], 422);
            }

            $request->validate(['counted_cash' => 'nullable|numeric|min:0']);

            $closingCash = $register->sales()->sum('total_amount');

            $register->update([
                'closing_cash' => $closingCash,
                'closed_at' => now(),
            ] + IntegrityMonitor::closeRegister($register, $request->input('counted_cash')));

            return response()->json([
                'success' => true,
                'message' => 'Register closed successfully.',
                'closing_cash' => $closingCash,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid action.'
        ], 422);
    }

    /**
     * Update register (deprecated - use finalize or updateStatus instead)
     * Kept for backward compatibility but marked for removal
     */
    public function update(Request $request, Register $register)
    {
        // Verify register belongs to user and is open
        if ((int)$register->user_id !== (int)auth()->id()) {
    abort(403, 'Unauthorized');
}

        if ($register->closed_at) {
            return back()->with('error', 'Register is already closed.');
        }

        $request->validate(['counted_cash' => (\App\Models\IntegrityEvent::cashCountRequired() ? 'required' : 'nullable') . '|numeric|min:0'], [
            'counted_cash.required' => 'Count the cash in the drawer and enter the amount before closing.',
        ]);

        // Closing cash is total of all transactions
        $closingCash = $register->sales()->sum('total_amount');

        // Update register
        $register->update([
            'closing_cash' => $closingCash,
            'closed_at' => now(),
        ] + IntegrityMonitor::closeRegister($register, $request->input('counted_cash')));

        return redirect()->route('dashboard')
            ->with('success', 'Register closed successfully. Total: MWK ' . number_format($closingCash, 2));
    }

    /**
     * Get currently open register for user
     */
    public static function getOpenRegister()
    {
        return Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();
    }

    /**
     * Check if user has open register (helper method)
     */
    public static function hasOpenRegister()
    {
        return Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->exists();
    }
}
