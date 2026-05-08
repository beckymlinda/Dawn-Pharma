<?php

namespace App\Http\Controllers;

use App\Models\Register;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RegisterReportController extends Controller
{
    public function index(Request $request)
    {
        // Determine date range based on request (matching other reports for consistency)
        $dateRange = $request->input('date_range', 'all_time');

        if ($dateRange === 'custom' && $request->has('from_date') && $request->has('to_date')) {
            $fromDate = Carbon::createFromFormat('Y-m-d', $request->input('from_date'))->startOfDay();
            $toDate   = Carbon::createFromFormat('Y-m-d', $request->input('to_date'))->endOfDay();
        } else {
            [$fromDate, $toDate] = $this->getDateRange($dateRange);
        }

        // Build query
        $query = Register::with(['user', 'location'])
            ->whereBetween('opened_at', [$fromDate, $toDate]);

        // Filter by user if selected
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by location if selected
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        // Search by user name
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Get pagination size (default 25)
        $perPage = (int) $request->input('per_page', 25);

        // Paginate results
        $registers = $query->orderBy('opened_at', 'desc')->paginate($perPage);

        // Calculate totals for each register
        $registersData = $registers->map(function ($register) {
            return [
                'id' => $register->id,
                'opened_at' => $register->opened_at,
                'closed_at' => $register->closed_at,
                'location_name' => $register->location?->name ?? 'N/A',
                'user_name' => $register->user->name ?? 'Unknown',
                'user_email' => $register->user->email ?? '',
                'total_cash' => $this->getTotalByPaymentMethod($register, 'cash'),
                'total_card' => $this->getTotalByPaymentMethod($register, 'card'),
                'total_cheques' => $this->getTotalByPaymentMethod($register, 'cheque'),
                'total_bank_transfer' => $this->getTotalByPaymentMethod($register, 'bank_transfer'),
                'total_advance' => $this->getTotalByPaymentMethod($register, 'advance'),
                'total_national_bank' => $this->getTotalByPaymentMethod($register, 'national_bank'),
                'total_standard_bank' => $this->getTotalByPaymentMethod($register, 'standard_bank'),
                'total_airtel_money' => $this->getTotalByPaymentMethod($register, 'airtel_money'),
                'total_mpamba' => $this->getTotalByPaymentMethod($register, 'mpamba'),
            ];
        });

        // Calculate summary totals across all (filtered) registers
        $summaryTotals = $this->calculateSummaryTotals($registers);

        // Get all users for filter dropdown
        $users = User::orderBy('name')->get();

        // Get all locations for filter dropdown
        $locations = Location::orderBy('name')->get();

        // Format currency
        $currency = 'MWK';

        return view('pos.register-report', [
            'registersData' => $registersData,
            'registers' => $registers,
            'users' => $users,
            'locations' => $locations,
            'summaryTotals' => $summaryTotals,
            'currency' => $currency,
            'fromDate' => $fromDate->format('Y-m-d'),
            'toDate' => $toDate->format('Y-m-d'),
            'dateRange' => $dateRange,
            'selectedUser' => $request->input('user_id'),
            'selectedLocation' => $request->input('location_id'),
            'search' => $request->input('search'),
            'perPage' => $perPage,
        ]);
    }

    /**
     * Helper function to get date range based on string input
     */
    private function getDateRange($rangeType)
    {
        $today = Carbon::now();

        switch ($rangeType) {
            case 'today':
                return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
            case 'yesterday':
                return [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()];
            case 'this_week':
                return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
            case 'last_7_days':
                return [$today->copy()->subDays(7)->startOfDay(), $today->copy()->endOfDay()];
            case 'last_30_days':
                return [$today->copy()->subDays(30)->startOfDay(), $today->copy()->endOfDay()];
            case 'this_month':
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
            case 'last_month':
                return [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()];
            case 'this_year':
                return [$today->copy()->startOfYear(), $today->copy()->endOfYear()];
            case 'all_time':
                return [Carbon::parse('2020-01-01'), $today->copy()->endOfDay()];
            default:
                return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
        }
    }

    /**
     * Export filtered data as CSV
     */
    public function exportCsv(Request $request)
    {
        $dateRange = $request->input('date_range', 'this_month');
        if ($dateRange === 'custom') {
            $fromDate = Carbon::parse($request->input('from_date'))->startOfDay();
            $toDate = Carbon::parse($request->input('to_date'))->endOfDay();
        } else {
            [$fromDate, $toDate] = $this->getDateRange($dateRange);
        }

        // Build query
        $query = Register::with(['user', 'location', 'sales'])
            ->whereBetween('opened_at', [$fromDate, $toDate]);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $registers = $query->orderBy('opened_at', 'desc')->get();

        // Create CSV content
        $csvData = "Open Time,Close Time,Location,User,Email,Total Card,Total Cheques,Total Cash,Total Bank Transfer,Total Advance,National Bank,Standard Bank,Airtel Money,Mpamba\n";

        foreach ($registers as $register) {
            $openTime = $register->opened_at->format('d-m-Y H:i');
            $closeTime = $register->closed_at ? $register->closed_at->format('d-m-Y H:i') : 'Open';
            $location = $register->location?->name ?? 'N/A';
            $userName = $register->user->name ?? 'Unknown';
            $userEmail = $register->user->email ?? '';
            
            $vals = [
                $this->getTotalByPaymentMethod($register, 'card'),
                $this->getTotalByPaymentMethod($register, 'cheque'),
                $this->getTotalByPaymentMethod($register, 'cash'),
                $this->getTotalByPaymentMethod($register, 'bank_transfer'),
                $this->getTotalByPaymentMethod($register, 'advance'),
                $this->getTotalByPaymentMethod($register, 'national_bank'),
                $this->getTotalByPaymentMethod($register, 'standard_bank'),
                $this->getTotalByPaymentMethod($register, 'airtel_money'),
                $this->getTotalByPaymentMethod($register, 'mpamba'),
            ];

            $csvData .= "\"$openTime\",\"$closeTime\",\"$location\",\"$userName\",\"$userEmail\"," . implode(',', $vals) . "\n";
        }

        // Create response
        $fileName = 'register-report-' . now()->format('Y-m-d-H-i-s') . '.csv';
        return response($csvData, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=$fileName");
    }

    /**
     * Get total sales amount by payment method
     */
    private function getTotalByPaymentMethod($register, $paymentMethod)
    {
        return $register->sales()
            ->where('payment_method', $paymentMethod)
            ->sum('total_amount');
    }

    /**
     * Calculate summary totals for all registers
     */
    private function calculateSummaryTotals($registers)
    {
        $totals = [
            'total_cash' => 0,
            'total_card' => 0,
            'total_cheques' => 0,
            'total_bank_transfer' => 0,
            'total_advance' => 0,
            'total_national_bank' => 0,
            'total_standard_bank' => 0,
            'total_airtel_money' => 0,
            'total_mpamba' => 0,
        ];

        foreach ($registers as $register) {
            $totals['total_cash'] += $this->getTotalByPaymentMethod($register, 'cash');
            $totals['total_card'] += $this->getTotalByPaymentMethod($register, 'card');
            $totals['total_cheques'] += $this->getTotalByPaymentMethod($register, 'cheque');
            $totals['total_bank_transfer'] += $this->getTotalByPaymentMethod($register, 'bank_transfer');
            $totals['total_advance'] += $this->getTotalByPaymentMethod($register, 'advance');
            $totals['total_national_bank'] += $this->getTotalByPaymentMethod($register, 'national_bank');
            $totals['total_standard_bank'] += $this->getTotalByPaymentMethod($register, 'standard_bank');
            $totals['total_airtel_money'] += $this->getTotalByPaymentMethod($register, 'airtel_money');
            $totals['total_mpamba'] += $this->getTotalByPaymentMethod($register, 'mpamba');
        }

        // Calculate grand total
        $totals['grand_total'] = array_sum($totals);

        return $totals;
    }
}
