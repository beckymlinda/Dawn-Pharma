@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid">
    @include('settings._tabs')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">Notifications</h5>
            <p class="text-muted small mb-0">Email and SMS a Z report automatically every time a register is closed.</p>
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

            <!-- SMS recipients -->
            <div class="card mt-4" id="sms">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                        <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2"></i>Send by SMS</h6>
                        @if($phones->where('is_active', true)->isNotEmpty() && $latestRegister && $smsReady)
                            <form method="POST" action="{{ route('notifications.zreport.sms') }}"
                                  onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Sending...';">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-send me-1"></i> Send test SMS</button>
                            </form>
                        @endif
                    </div>

                    @unless($smsReady)
                        <div class="alert alert-warning small py-2">
                            SMS is not set up on this server yet (TUMASEND_API_KEY is missing in .env). Numbers can be added now and will receive texts once it is set.
                        </div>
                    @endunless

                    @if($phones->isEmpty())
                        {{-- Prompt, like the email list starts with the login email --}}
                        <div class="sms-prompt mb-3">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-phone-vibrate fs-3 text-primary"></i>
                                <div>
                                    <div class="fw-semibold">Get the Z report on your phone</div>
                                    <div class="small text-muted">Add a phone number to receive a one-SMS summary (each payment method and the total sales) every time a register closes.</div>
                                    @if($suggestedPhone)
                                        <form method="POST" action="{{ route('notifications.phones.store') }}" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="phone" value="{{ $suggestedPhone }}">
                                            <input type="hidden" name="name" value="Pharmacy phone">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus-lg me-1"></i> Use the pharmacy number {{ (new \App\Models\SmsRecipient(['phone' => $suggestedPhone]))->display_phone }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('notifications.phones.store') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-6">
                            <input type="tel" name="phone" inputmode="tel" class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="0991 234 567" value="{{ old('phone') }}" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control" placeholder="Name (optional)" value="{{ old('phone') ? old('name') : '' }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                @forelse($phones as $phone)
                                    <tr class="{{ $phone->is_active ? '' : 'opacity-50' }}">
                                        <td>
                                            <div class="fw-semibold">{{ $phone->display_phone }}</div>
                                            <small class="text-muted">
                                                {{ $phone->name }}
                                                @if(!$phone->is_active)
                                                    <span class="badge bg-secondary ms-1">Paused</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <form method="POST" action="{{ route('notifications.phones.toggle', $phone) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $phone->is_active ? 'Pause' : 'Resume' }}">
                                                    <i class="bi {{ $phone->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('notifications.phones.destroy', $phone) }}" class="d-inline"
                                                  onsubmit="return confirm('Stop sending Z reports by SMS to {{ $phone->display_phone }}?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted small">No phone numbers yet. Z reports are only emailed.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Each text uses SMS credits on TumaSend (1 credit per number per report).</p>
                </div>
            </div>
        </div>

        <!-- Sent log -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Recently Sent</h6>
                        @if($logs->total() > 0)
                            <form method="POST" action="{{ route('notifications.logs.clear') }}"
                                  onsubmit="return confirm('Clear the whole sent list? This only removes the history, not the recipients.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i> Clear all</button>
                            </form>
                        @endif
                    </div>
                    @forelse($logs as $log)
                        <div class="border-bottom py-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">
                                    <i class="bi {{ $log->type === 'z_report_sms' ? 'bi-chat-dots' : 'bi-envelope' }} me-1 text-muted" title="{{ $log->type === 'z_report_sms' ? 'SMS' : 'Email' }}"></i>
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
                        <p class="text-muted small mb-0">No Z reports sent yet.</p>
                    @endforelse
                    <div class="mt-3">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .sms-prompt { background: #f0f9ff; border: 1px dashed #7dd3fc; border-radius: 12px; padding: .9rem 1rem; }
</style>
@endsection
