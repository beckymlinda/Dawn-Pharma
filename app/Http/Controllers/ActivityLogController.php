<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        $query = ActivityLog::with(['user', 'subject'])
            ->latest('created_at');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            // Default to last 7 days if no date range specified
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $activities = $query->paginate(50);

        // Get available users for filter
        $users = User::orderBy('name')->pluck('name', 'id');

        // Get available actions for filter
        $actions = ActivityLog::distinct('action')
            ->orderBy('action')
            ->pluck('action');

        // Get statistics
        $stats = [
            'total_activities' => ActivityLog::count(),
            'today_activities' => ActivityLog::whereDate('created_at', today())->count(),
            'this_week_activities' => ActivityLog::where('created_at', '>=', Carbon::now()->startOfWeek())->count(),
            'this_month_activities' => ActivityLog::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
        ];

        return view('activity-log.index', compact('activities', 'users', 'actions', 'stats', 'request'));
    }

    public function show(ActivityLog $activityLog)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }
        $activityLog->load(['user', 'subject']);
        return view('activity-log.show', compact('activityLog'));
    }

    public function destroy(ActivityLog $activityLog)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }
        $activityLog->delete();
        return redirect()->route('activity-log.index')
            ->with('success', 'Activity log deleted successfully.');
    }

    public function clearOldLogs()
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }
        // Delete logs older than 90 days
        $deletedCount = ActivityLog::where('created_at', '<', Carbon::now()->subDays(90))
            ->delete();

        return redirect()->route('activity-log.index')
            ->with('success', "Deleted {$deletedCount} old activity logs.");
    }

    public function export(Request $request)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }
        $query = ActivityLog::with(['user', 'subject']);

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $activities = $query->latest('created_at')->get();

        $filename = "activity_log_" . date('Y-m-d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($activities) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'Date',
                'User',
                'Action',
                'Description',
                'Subject Type',
                'Subject ID',
                'IP Address',
                'User Agent'
            ]);

            // CSV Data
            foreach ($activities as $activity) {
                fputcsv($file, [
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->user->name ?? 'System',
                    $activity->action,
                    $activity->description,
                    $activity->subject_type,
                    $activity->subject_id,
                    $activity->ip_address,
                    $activity->user_agent
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
