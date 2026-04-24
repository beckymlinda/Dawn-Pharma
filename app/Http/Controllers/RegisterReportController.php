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
        // Get date range from request or use default (current month)
        $fromDate = $request->input('from_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('from_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('to_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        // Build query
        $query = Register::with(['user', 'location', 'sales'])
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
            ];
        });

        // Calculate summary totals across all (filtered) registers
        $summaryTotals = $this->calculateSummaryTotals($registers);

        // Get distinct users for filter dropdown
        $users = User::whereHas('registers')
            ->orderBy('name')
            ->get();

        // Get distinct locations for filter dropdown
        $locations = Location::has('registers')
            ->orderBy('name')
            ->get();

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
            'selectedUser' => $request->input('user_id'),
            'selectedLocation' => $request->input('location_id'),
            'search' => $request->input('search'),
            'perPage' => $perPage,
        ]);
    }

    /**
     * Export filtered data as CSV
     */
    public function exportCsv(Request $request)
    {
        // Get date range from request or use default (current month)
        $fromDate = $request->input('from_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('from_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $toDate = $request->input('to_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('to_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

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
        $csvData = "Open Time,Close Time,Location,User,Email,Total Card,Total Cheques,Total Cash,Total Bank Transfer,Total Advance,National Bank,Standard Bank\n";

        foreach ($registers as $register) {
            $openTime = $register->opened_at->format('d-m-Y H:i');
            $closeTime = $register->closed_at ? $register->closed_at->format('d-m-Y H:i') : 'Open';
            $location = $register->location?->name ?? 'N/A';
            $userName = $register->user->name ?? 'Unknown';
            $userEmail = $register->user->email ?? '';
            
            $totalCard = $this->getTotalByPaymentMethod($register, 'card');
            $totalCheques = $this->getTotalByPaymentMethod($register, 'cheque');
            $totalCash = $this->getTotalByPaymentMethod($register, 'cash');
            $totalBankTransfer = $this->getTotalByPaymentMethod($register, 'bank_transfer');
            $totalAdvance = $this->getTotalByPaymentMethod($register, 'advance');
            $totalNationalBank = $this->getTotalByPaymentMethod($register, 'national_bank');
            $totalStandardBank = $this->getTotalByPaymentMethod($register, 'standard_bank');

            $csvData .= "\"$openTime\",\"$closeTime\",\"$location\",\"$userName\",\"$userEmail\",$totalCard,$totalCheques,$totalCash,$totalBankTransfer,$totalAdvance,$totalNationalBank,$totalStandardBank\n";
        }

        // Add summary row
        $summary = $this->calculateSummaryTotals($registers);
        $csvData .= "\nSUMMARY,,,,,";
        $csvData .= "{$summary['total_card']},{$summary['total_cheques']},{$summary['total_cash']},{$summary['total_bank_transfer']},{$summary['total_advance']},{$summary['total_national_bank']},{$summary['total_standard_bank']}\n";

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
        ];

        foreach ($registers as $register) {
            $totals['total_cash'] += $this->getTotalByPaymentMethod($register, 'cash');
            $totals['total_card'] += $this->getTotalByPaymentMethod($register, 'card');
            $totals['total_cheques'] += $this->getTotalByPaymentMethod($register, 'cheque');
            $totals['total_bank_transfer'] += $this->getTotalByPaymentMethod($register, 'bank_transfer');
            $totals['total_advance'] += $this->getTotalByPaymentMethod($register, 'advance');
            $totals['total_national_bank'] += $this->getTotalByPaymentMethod($register, 'national_bank');
            $totals['total_standard_bank'] += $this->getTotalByPaymentMethod($register, 'standard_bank');
        }

        // Calculate grand total
        $totals['grand_total'] = array_sum([
            $totals['total_cash'],
            $totals['total_card'],
            $totals['total_cheques'],
            $totals['total_bank_transfer'],
            $totals['total_advance'],
            $totals['total_national_bank'],
            $totals['total_standard_bank'],
        ]);

        return $totals;
    }
}
