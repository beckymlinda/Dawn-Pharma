{{-- Shop details shared by the create and edit forms; $tenant is null when creating --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label small fw-semibold" for="name">Shop name</label>
        <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $tenant?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold" for="plan">Plan</label>
        <select id="plan" name="plan" class="form-select">
            <option value="">No plan</option>
            @foreach($plans as $key => $plan)
                <option value="{{ $key }}" @selected(old('plan', $tenant?->plan) === $key)>{{ $plan['name'] }} – {{ formatCurrency($plan['price']) }}/month</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold" for="paid_until">Paid until</label>
        <input id="paid_until" type="date" name="paid_until" class="form-control @error('paid_until') is-invalid @enderror"
               value="{{ old('paid_until', $tenant?->paid_until?->toDateString() ?? ($tenant ? '' : now()->addMonth()->toDateString())) }}">
        <div class="form-text">Empty = no end date</div>
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="contact_name">Contact person</label>
        <input id="contact_name" name="contact_name" class="form-control" value="{{ old('contact_name', $tenant?->contact_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="contact_phone">Contact phone</label>
        <input id="contact_phone" name="contact_phone" class="form-control" value="{{ old('contact_phone', $tenant?->contact_phone) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold" for="contact_email">Contact email</label>
        <input id="contact_email" type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', $tenant?->contact_email) }}">
        @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold" for="grace_days">Grace days after due date</label>
        <input id="grace_days" type="number" min="0" max="90" name="grace_days" class="form-control"
               value="{{ old('grace_days', $tenant?->grace_days ?? config('tenancy.default_grace_days')) }}" required>
    </div>
    <div class="col-md-9">
        <label class="form-label small fw-semibold" for="notes">Notes (only visible here)</label>
        <input id="notes" name="notes" class="form-control" value="{{ old('notes', $tenant?->notes) }}">
    </div>
</div>
