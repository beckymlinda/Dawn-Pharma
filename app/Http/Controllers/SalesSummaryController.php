<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Http\Request;

class SalesSummaryController extends Controller
{
    public function index()
    {
        return view('sales.summary');
    }

    public function getPaymentMethodSummary(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // Debug: Log input parameters
            \Log::info('SalesSummary Debug - Input: ' . json_encode([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'current_cat_time' => now()->setTimezone('Africa/Blantyre')->format('Y-m-d H:i:s')
            ]));
            
            // Query payments directly - payments table is the single source of truth
            $query = Payment::query();
            
            // Apply date filtering directly on payments.created_at with CAT timezone
            if ($startDate) {
                $startDateTime = \Carbon\Carbon::parse($startDate, 'Africa/Blantyre')->startOfDay()->setTimezone('UTC');
                $query->where('created_at', '>=', $startDateTime);
                \Log::info('Start filter applied: ' . $startDateTime);
            }
            
            if ($endDate) {
                $endDateTime = \Carbon\Carbon::parse($endDate, 'Africa/Blantyre')->endOfDay()->setTimezone('UTC');
                $query->where('created_at', '<=', $endDateTime);
                \Log::info('End filter applied: ' . $endDateTime);
            }
            
            // Get all payments within date range - no sales table dependency
            $payments = $query->get();
            
            // Debug: Log payment count and total before filtering
            \Log::info('Payments retrieved: ' . $payments->count() . ' payments, total: ' . $payments->sum('amount'));
            
            // Debug: Log all payment methods and amounts
            $paymentDebug = $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'created_at' => $payment->created_at,
                    'sale_id' => $payment->sale_id
                ];
            })->toArray();
            \Log::info('Payment details: ' . json_encode($paymentDebug));
            
            // Group payments by method and sum amounts directly from payments table
            $summary = $payments->groupBy('method')->map(function ($paymentGroup, $method) {
                return [
                    'method' => $method,
                    'total_amount' => $paymentGroup->sum('amount'),
                    'count' => $paymentGroup->count(),
                    'transactions' => $paymentGroup->pluck('sale_id')->unique()->count(), // Unique sale count
                    'debug_payments' => $paymentGroup->map(function($p) {
                        return ['id' => $p->id, 'amount' => $p->amount, 'created_at' => $p->created_at];
                    })->toArray()
                ];
            })->values()->sortByDesc('total_amount');
            
            // Calculate totals directly from payments
            $totalRevenue = $payments->sum('amount');
            $totalTransactions = $payments->count();
            $totalUniqueSales = $payments->pluck('sale_id')->unique()->count();
            
            // Get payment method breakdown for validation
            $paymentBreakdown = $payments->groupBy('method')->mapWithKeys(function ($paymentGroup, $method) {
                return [$method => [
                    'amount' => $paymentGroup->sum('amount'),
                    'count' => $paymentGroup->count()
                ]];
            });
            
            // Debug: Log final totals
            \Log::info('Final totals: ' . json_encode([
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'payment_breakdown' => $paymentBreakdown->toArray()
            ]));
            
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'breakdown' => $paymentBreakdown,
                'debug' => [
                    'input_params' => ['start_date' => $startDate, 'end_date' => $endDate],
                    'payments_count' => $payments->count(),
                    'raw_total' => $payments->sum('amount'),
                    'payment_methods_found' => $payments->pluck('method')->unique()->toArray()
                ],
                'totals' => [
                    'total_revenue' => $totalRevenue,
                    'total_transactions' => $totalTransactions,
                    'total_unique_sales' => $totalUniqueSales,
                    'validation' => [
                        'sum_of_methods_equals_total' => abs($paymentBreakdown->sum('amount') - $totalRevenue) < 0.01
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('SalesSummary Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to load payment summary: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test method to show all payments without filtering
     */
    public function getAllPaymentsTest(Request $request)
    {
        try {
            // Get ALL payments without any date filtering
            $allPayments = Payment::all();
            
            // Get today's payments using simple date comparison
            $today = now()->format('Y-m-d');
            $todayPayments = Payment::whereDate('created_at', $today)->get();
            
            return response()->json([
                'success' => true,
                'test_results' => [
                    'total_payments_in_db' => $allPayments->count(),
                    'total_amount_in_db' => $allPayments->sum('amount'),
                    'today_payments_simple' => $todayPayments->count(),
                    'today_amount_simple' => $todayPayments->sum('amount'),
                    'today_methods_simple' => $todayPayments->pluck('method')->unique()->toArray(),
                    'today_payment_details' => $todayPayments->map(function($p) {
                        return [
                            'id' => $p->id,
                            'amount' => $p->amount,
                            'method' => $p->method,
                            'created_at' => $p->created_at,
                            'created_at_date' => $p->created_at->format('Y-m-d')
                        ];
                    })->toArray()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate payment data consistency
     */
    public function validatePaymentData(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // Get payments within date range
            $query = Payment::query();
            
            if ($startDate) {
                $startDateTime = \Carbon\Carbon::parse($startDate, 'Africa/Blantyre')->startOfDay()->setTimezone('UTC');
                $query->where('created_at', '>=', $startDateTime);
            }
            
            if ($endDate) {
                $endDateTime = \Carbon\Carbon::parse($endDate, 'Africa/Blantyre')->endOfDay()->setTimezone('UTC');
                $query->where('created_at', '<=', $endDateTime);
            }
            
            $payments = $query->get();
            
            // Validation checks
            $validation = [
                'total_payments' => $payments->count(),
                'total_amount' => $payments->sum('amount'),
                'unique_sales' => $payments->pluck('sale_id')->unique()->count(),
                'payment_methods' => $payments->groupBy('method')->mapWithKeys(function ($group, $method) {
                    return [$method => [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount'),
                        'unique_sales' => $group->pluck('sale_id')->unique()->count()
                    ]];
                }),
                'orphaned_payments' => $payments->whereNull('sale_id')->count(),
                'split_payment_sales' => $payments->pluck('sale_id')->unique()->filter(function($saleId) use ($payments) {
                    return $payments->where('sale_id', $saleId)->count() > 1;
                })->count(),
                'data_integrity' => [
                    'all_payments_have_amount' => $payments->where('amount', '>', 0)->count() === $payments->count(),
                    'all_payments_have_method' => $payments->where('method', '!=', '')->count() === $payments->count(),
                    'no_negative_amounts' => $payments->where('amount', '<', 0)->count() === 0
                ]
            ];
            
            // Check if sum of payment methods equals total
            $methodSum = $validation['payment_methods']->sum('amount');
            $validation['sum_validation'] = [
                'methods_sum' => $methodSum,
                'total_amount' => $validation['total_amount'],
                'matches' => abs($methodSum - $validation['total_amount']) < 0.01,
                'difference' => abs($methodSum - $validation['total_amount'])
            ];
            
            return response()->json([
                'success' => true,
                'validation' => $validation,
                'issues' => [
                    'orphaned_payments_found' => $validation['orphaned_payments'] > 0,
                    'data_integrity_issues' => !$validation['data_integrity']['all_payments_have_amount'] || 
                                            !$validation['data_integrity']['all_payments_have_method'] || 
                                            !$validation['data_integrity']['no_negative_amounts'],
                    'sum_mismatch' => !$validation['sum_validation']['matches']
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to validate payment data: ' . $e->getMessage()
            ], 500);
        }
    }
}
