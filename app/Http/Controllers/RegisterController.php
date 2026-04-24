<?php

namespace App\Http\Controllers;

use App\Models\Register;
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
            ->with('sales')
            ->first();

        if (!$openRegister) {
            return back()->with('error', 'No open register to close.');
        }

        // Calculate totals and counts by payment method
        $sales = $openRegister->sales;
        
        $totals = [
            'total_cash' => $sales->where('payment_method', 'cash')->sum('total_amount'),
            'total_card' => $sales->where('payment_method', 'card')->sum('total_amount'),
            'total_cheques' => $sales->where('payment_method', 'cheque')->sum('total_amount'),
            'total_bank_transfer' => $sales->where('payment_method', 'bank_transfer')->sum('total_amount'),
            'total_advance' => $sales->where('payment_method', 'advance')->sum('total_amount'),
            'total_standard_bank' => $sales->where('payment_method', 'standard_bank')->sum('total_amount'),
            'total_national_bank' => $sales->where('payment_method', 'national_bank')->sum('total_amount'),
            'total_mpamba' => $sales->where('payment_method', 'mpamba')->sum('total_amount'),
            'total_airtel_money' => $sales->where('payment_method', 'airtel_money')->sum('total_amount'),
            'total_cash_and_card' => $sales->where('payment_method', 'cash_and_card')->sum('total_amount'),
            'total_sales' => $sales->sum('total_amount'),
        ];

        // Calculate counts (frequency) for each payment method
        $counts = [
            'count_cash' => $sales->where('payment_method', 'cash')->count(),
            'count_card' => $sales->where('payment_method', 'card')->count(),
            'count_cheques' => $sales->where('payment_method', 'cheque')->count(),
            'count_bank_transfer' => $sales->where('payment_method', 'bank_transfer')->count(),
            'count_advance' => $sales->where('payment_method', 'advance')->count(),
            'count_standard_bank' => $sales->where('payment_method', 'standard_bank')->count(),
            'count_national_bank' => $sales->where('payment_method', 'national_bank')->count(),
            'count_mpamba' => $sales->where('payment_method', 'mpamba')->count(),
            'count_airtel_money' => $sales->where('payment_method', 'airtel_money')->count(),
            'count_cash_and_card' => $sales->where('payment_method', 'cash_and_card')->count(),
            'count_total_sales' => $sales->count(),
        ];

        return view('pos.register.close', [
            'register' => $openRegister,
            'totals' => $totals,
            'counts' => $counts,
        ]);
    }

    /**
     * Close register session
     */
    public function update(Request $request, Register $register)
    {
        // Verify register belongs to user and is open
        if ($register->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if ($register->closed_at) {
            return back()->with('error', 'Register is already closed.');
        }

        // Get all sales for this register
        $sales = $register->sales;

        // Calculate totals by payment method
        $totalCash = $sales->where('payment_method', 'cash')->sum('total_amount');
        $totalCard = $sales->where('payment_method', 'card')->sum('total_amount');
        $totalCheques = $sales->where('payment_method', 'cheque')->sum('total_amount');
        $totalBankTransfer = $sales->where('payment_method', 'bank_transfer')->sum('total_amount');
        $totalAdvance = $sales->where('payment_method', 'advance')->sum('total_amount');

        // Closing cash is total of all cash transactions
        $closingCash = $totalCash + $totalCard + $totalCheques + $totalBankTransfer + $totalAdvance;

        // Update register
        $register->update([
            'closing_cash' => $closingCash,
            'closed_at' => now(),
        ]);

        return redirect()->route('reports.register-report')
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
