@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Notifications</h4>
            <p class="text-muted small mb-0">Email a Z report automatically every time a register is closed.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <!-- Z report setting -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h6 class="fw-bold mb-1"><i class="bi bi-receipt me-2"></i>Z Report on Register Close</h6>
                            <p class="text-muted small mb-0">
                                Includes payments by method, cash drawer total, products sold, profit and every
                                transaction in the shift, with a CSV copy attached.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('notifications.settings') }}">
                            @csrf
                            <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
                            <button type="submit" class="btn btn-sm {{ $enabled ? 'btn-success' : 'btn-outline-secondary' }} text-nowrap">
                                <i class="bi {{ $enabled ? 'bi-toggle-on' : 'bi-toggle-off' }} me-1"></i>
                                {{ $enabled ? 'On' : 'Off' }}
                            </button>
                        </form>
                    </div>

                    @if($latestRegister)
                        <hr>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="small text-muted me-auto">
                                Latest register: #{{ $latestRegister->id }} · {{ $latestRegister->user->name ?? 'Unknown' }}
                                · {{ $latestRegister->closed_at ? 'closed ' . $latestRegister->closed_at->format('M d, H:i') : 'still open' }}
                            </span>
                            <a href="{{ route('notifications.zreport.preview') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i> Preview Email
                            </a>
                            <form method="POST" action="{{ route('notifications.zreport.send') }}"
                                  onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Sending...';">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-send me-1"></i> Send Now
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recipients -->
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-envelope me-2"></i>Send To</h6>

                    <form method="POST" action="{{ route('notifications.recipients.store') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-6">
                            <input type="email" name="email" class="form-control" placeholder="email@example.com"
                                   value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control" placeholder="Name (optional)" value="{{ old('name') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                @forelse($recipients as $recipient)
                                    <tr class="{{ $recipient->is_active ? '' : 'opacity-50' }}">
                                        <td>
                                            <div class="fw-semibold">{{ $recipient->email }}</div>
                                            <small class="text-muted">
                                                {{ $recipient->name }}
                                                @if($recipient->is_default)
                                                    <span class="badge bg-primary-subtle text-primary-emphasis ms-1">Default (login email)</span>
                                                @endif
                                                @if(!$recipient->is_active)
                                                    <span class="badge bg-secondary ms-1">Paused</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <form method="POST" action="{{ route('notifications.recipients.toggle', $recipient) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                        title="{{ $recipient->is_active ? 'Pause' : 'Resume' }}">
                                                    <i class="bi {{ $recipient->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('notifications.recipients.destroy', $recipient) }}" class="d-inline"
                                                  onsubmit="return confirm('Stop sending Z reports to {{ $recipient->email }}?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted small">
                                            No emails added. Z reports will go to the login email of the cashier who closes the register.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sent log -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Recently Sent</h6>
                    @forelse($logs as $log)
                        <div class="border-bottom py-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">
                                    Register #{{ $log->register_id }} · {{ $log->register->user->name ?? '' }}
                                </span>
                                <span class="badge {{ $log->status === 'sent' ? 'bg-success' : 'bg-danger' }}">{{ ucfirst($log->status) }}</span>
                            </div>
                            <div class="text-muted">{{ $log->created_at->format('M d, Y H:i') }}
                                · {{ $log->triggeredBy ? 'by ' . $log->triggeredBy->name : 'automatic' }}</div>
                            <div class="text-muted text-break">To: {{ $log->recipients ?: '—' }}</div>
                            @if($log->error)
                                <div class="text-danger text-break">{{ \Illuminate\Support\Str::limit($log->error, 200) }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No Z report emails sent yet.</p>
                    @endforelse
                    <div class="mt-3">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
