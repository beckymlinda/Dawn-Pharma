{{-- Billing state badge for a shop --}}
@php
    $state = $tenant->billingState();
    $days = $tenant->daysLeft();
    [$class, $label] = match ($state) {
        'suspended' => ['bg-dark', 'Suspended'],
        'expired' => ['bg-danger', 'Expired – blocked'],
        'grace' => ['bg-danger-subtle text-danger-emphasis', 'Overdue ' . abs($days) . 'd (grace)'],
        'due_soon' => ['bg-warning-subtle text-warning-emphasis', $days === 0 ? 'Due today' : 'Due in ' . $days . 'd'],
        default => ['bg-success-subtle text-success-emphasis', $tenant->paid_until ? 'Paid' : 'No end date'],
    };
@endphp
<span class="badge rounded-pill state-badge {{ $class }}">{{ $label }}</span>
