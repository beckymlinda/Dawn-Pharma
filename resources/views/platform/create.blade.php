@extends('platform.layout')
@section('title', 'New shop')

@section('content')
<a href="{{ route('platform.tenants.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All shops</a>
<h1 class="h3 fw-bold mt-2 mb-4">New shop</h1>

<form method="POST" action="{{ route('platform.tenants.store') }}">
    @csrf
    <div class="card mb-3">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-3">Shop</h2>
            <div class="mb-3" style="max-width: 520px;">
                <label class="form-label small fw-semibold" for="slug">Shop code (part of the shop's link)</label>
                <div class="input-group">
                    <span class="input-group-text">{{ url('/') }}/</span>
                    <input id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror"
                           value="{{ old('slug') }}" placeholder="area25" required pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?" maxlength="63" autocomplete="off" autocapitalize="none">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text">Lowercase letters, numbers and dashes. Staff open this link (or type the code at login). It cannot be changed later.</div>
            </div>
            @include('platform._shop_fields', ['tenant' => null])
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body p-4">
            <h2 class="h6 fw-bold mb-1">Owner's login</h2>
            <p class="text-muted small mb-3">The shop's first user, with full access (Super Admin). They add their own staff.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="owner_name">Name</label>
                    <input id="owner_name" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="owner_email">Email (used to log in)</label>
                    <input id="owner_email" type="email" name="owner_email" class="form-control @error('owner_email') is-invalid @enderror" value="{{ old('owner_email') }}" required>
                    @error('owner_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="owner_password">Password</label>
                    <input id="owner_password" type="password" name="owner_password" class="form-control @error('owner_password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                    @error('owner_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold" for="owner_password_confirmation">Repeat password</label>
                    <input id="owner_password_confirmation" type="password" name="owner_password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary px-4">Create shop</button>
</form>
@endsection
