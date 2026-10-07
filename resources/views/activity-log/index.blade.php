@extends('layouts.app')

@section('title', 'Activity Log - ' . setting('system_name'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Activity Log</h1>
            <p class="text-muted">Track all user activities and system events</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="{{ route('activity-log.export', $request->query()) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-download"></i> Export
                </a>
                <form action="{{ route('activity-log.clear-old') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                            onclick="return confirm('This will delete all activity logs older than 90 days. Are you sure?')">
                        <i class="bi bi-trash"></i> Clear Old Logs
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Activities</h5>
                    <h3 class="card-text">{{ number_format($stats['total_activities']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Today</h5>
                    <h3 class="card-text">{{ number_format($stats['today_activities']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">This Week</h5>
                    <h3 class="card-text">{{ number_format($stats['this_week_activities']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">This Month</h5>
                    <h3 class="card-text">{{ number_format($stats['this_month_activities']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('activity-log.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">User</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">All Users</option>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select form-select-sm">
                            <option value="">All Actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                    {{ ucfirst($action) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" 
                               value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" 
                               value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" 
                               placeholder="Search description..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('activity-log.index') }}" class="btn btn-secondary btn-sm">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity List -->
    <div class="card">
        <div class="card-body">
            @if($activities->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Subject</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr>
                                    <td>
                                        <small>{{ $activity->created_at->format('M d, Y H:i') }}</small><br>
                                        <span class="text-muted">{{ $activity->time_ago }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 30px; height: 30px; font-size: 12px;">
                                                {{ strtoupper(substr($activity->user->name ?? 'System', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $activity->user->name ?? 'System' }}</div>
                                                <small class="text-muted">{{ $activity->user->email ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $activity->action_color }}">
                                            <i class="bi {{ $activity->action_icon }}"></i>
                                            {{ ucfirst($activity->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $activity->description ?? '-' }}
                                    </td>
                                    <td>
                                        @if($activity->subject_type && $activity->subject_id)
                                            <small class="text-muted">
                                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $activity->ip_address ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('activity-log.show', $activity) }}" 
                                               class="btn btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <form action="{{ route('activity-log.destroy', $activity) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this activity log?')"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing {{ $activities->firstItem() }} to {{ $activities->lastItem() }} 
                        of {{ $activities->total() }} entries
                    </div>
                    {{ $activities->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-clock-history" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No activity logs found.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
