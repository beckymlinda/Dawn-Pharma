@extends('layouts.app')

@section('title', 'Activity Details - ' . setting('system_name'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Activity Details</h1>
            <p class="text-muted">Detailed information about this activity</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('activity-log.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Activity Log
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Activity Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted" width="150">Date & Time:</td>
                                    <td>
                                        <strong>{{ $activityLog->created_at->format('M d, Y H:i:s') }}</strong><br>
                                        <small class="text-muted">{{ $activityLog->time_ago }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">User:</td>
                                    <td>
                                        @if($activityLog->user)
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 30px; height: 30px; font-size: 12px;">
                                                    {{ strtoupper(substr($activityLog->user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $activityLog->user->name }}</div>
                                                    <small class="text-muted">{{ $activityLog->user->email }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Action:</td>
                                    <td>
                                        <span class="badge bg-{{ $activityLog->action_color }} fs-6">
                                            <i class="bi {{ $activityLog->action_icon }}"></i>
                                            {{ ucfirst($activityLog->action) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Description:</td>
                                    <td>{{ $activityLog->description ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted" width="150">Subject Type:</td>
                                    <td>{{ $activityLog->subject_type ? class_basename($activityLog->subject_type) : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Subject ID:</td>
                                    <td>{{ $activityLog->subject_id ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">IP Address:</td>
                                    <td>{{ $activityLog->ip_address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">User Agent:</td>
                                    <td>
                                        <small class="text-muted">{{ $activityLog->user_agent ?? '-' }}</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Details -->
            @if($activityLog->subject)
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Subject Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Subject:</strong> {{ class_basename($activityLog->subject_type) }} #{{ $activityLog->subject_id }}
                        </div>
                        
                        @switch($activityLog->subject_type)
                            @case('App\\Models\\Product')
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Product Name:</strong> {{ $activityLog->subject->name ?? '-' }}<br>
                                        <strong>SKU:</strong> {{ $activityLog->subject->sku ?? '-' }}<br>
                                        <strong>Current Stock:</strong> {{ $activityLog->subject->current_stock ?? 0 }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Purchase Price:</strong> MWK {{ number_format($activityLog->subject->purchase_price ?? 0, 2) }}<br>
                                        <strong>Selling Price:</strong> MWK {{ number_format($activityLog->subject->selling_price ?? 0, 2) }}<br>
                                        <strong>Category:</strong> {{ $activityLog->subject->category->name ?? '-' }}
                                    </div>
                                </div>
                                @break
                            
                            @case('App\\Models\\Sale')
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Invoice No:</strong> {{ $activityLog->subject->invoice_no ?? '-' }}<br>
                                        <strong>Customer:</strong> {{ $activityLog->subject->customer_name ?? '-' }}<br>
                                        <strong>Total Amount:</strong> MWK {{ number_format($activityLog->subject->total_amount ?? 0, 2) }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Payment Method:</strong> {{ $activityLog->subject->payment_method ?? '-' }}<br>
                                        <strong>Payment Status:</strong> {{ $activityLog->subject->payment_status ?? '-' }}<br>
                                        <strong>Items Count:</strong> {{ $activityLog->subject->total_items ?? 0 }}
                                    </div>
                                </div>
                                @break
                            
                            @case('App\\Models\\Purchase')
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Reference No:</strong> {{ $activityLog->subject->reference_no ?? '-' }}<br>
                                        <strong>Supplier:</strong> {{ $activityLog->subject->supplier->name ?? '-' }}<br>
                                        <strong>Grand Total:</strong> MWK {{ number_format($activityLog->subject->grand_total ?? 0, 2) }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Status:</strong> {{ $activityLog->subject->status ?? '-' }}<br>
                                        <strong>Payment Status:</strong> {{ $activityLog->subject->payment_status ?? '-' }}<br>
                                        <strong>Purchase Date:</strong> {{ $activityLog->subject->purchase_date?->format('M d, Y') ?? '-' }}
                                    </div>
                                </div>
                                @break
                            
                            @default
                                <div class="alert alert-warning">
                                    Subject details are not available for this type of record.
                                </div>
                        @endswitch
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('activity-log.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-list"></i> View All Activities
                        </a>
                        
                        @if($activityLog->subject)
                            @switch($activityLog->subject_type)
                                @case('App\\Models\\Product')
                                    <a href="{{ route('products.edit', $activityLog->subject_id) }}" class="btn btn-outline-warning">
                                        <i class="bi bi-pencil"></i> Edit Product
                                    </a>
                                    @break
                                
                                @case('App\\Models\\Sale')
                                    <a href="{{ route('sales.show', $activityLog->subject_id) }}" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i> View Sale
                                    </a>
                                    @break
                                
                                @case('App\\Models\\Purchase')
                                    <a href="{{ route('purchases.show', $activityLog->subject_id) }}" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i> View Purchase
                                    </a>
                                    @break
                            @endswitch
                        @endif
                        
                        <form action="{{ route('activity-log.destroy', $activityLog) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100" 
                                    onclick="return confirm('Are you sure you want to delete this activity log?')">
                                <i class="bi bi-trash"></i> Delete Activity
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Related Activities -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Related Activities</h6>
                </div>
                <div class="card-body">
                    @if($activityLog->user)
                        <p class="text-muted mb-3">Other activities by {{ $activityLog->user->name }}:</p>
                        
                        @php
                            $relatedActivities = \App\Models\ActivityLog::where('user_id', $activityLog->user_id)
                                ->where('id', '!=', $activityLog->id)
                                ->latest()
                                ->take(5)
                                ->get();
                        @endphp
                        
                        @if($relatedActivities->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($relatedActivities as $related)
                                    <a href="{{ route('activity-log.show', $related) }}" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-{{ $related->action_color }} me-2">
                                                    {{ ucfirst($related->action) }}
                                                </span>
                                                <small>{{ $related->description }}</small>
                                            </div>
                                            <small class="text-muted">{{ $related->time_ago }}</small>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">No other activities found.</p>
                        @endif
                    @else
                        <p class="text-muted">No user information available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
