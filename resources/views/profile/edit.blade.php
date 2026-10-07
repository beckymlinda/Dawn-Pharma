@extends('layouts.app')

@section('title', 'My Profile - ' . setting('system_name'))

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                     style="width: 56px; height: 56px; font-size: 1.3rem; background: var(--theme-primary, #004d3d);" aria-hidden="true">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h4 class="fw-bold mb-0 text-truncate">{{ $user->name }}</h4>
                    <div class="text-muted small">
                        {{ $user->email }}
                        @if($user->roles->isNotEmpty())
                            · {{ $user->roles->pluck('display_name')->filter()->join(', ') ?: $user->roles->pluck('name')->join(', ') }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Name and email --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-1"><i class="bi bi-person me-2"></i>My details</h6>
                            <p class="text-muted small mb-4">Your name appears on receipts, reports and the activity log.</p>

                            @if (session('status') === 'profile-updated')
                                <div class="alert alert-success py-2 small" role="status">Your details were saved.</div>
                            @endif

                            <form method="POST" action="{{ route('profile.update') }}">
                                @csrf
                                @method('PATCH')

                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold small">Name</label>
                                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $user->name) }}" required autocomplete="name">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold small">Email (used to log in)</label>
                                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $user->email) }}" required autocomplete="username">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <button type="submit" class="btn btn-primary px-4">Save details</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Password --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2"></i>Change password</h6>
                            <p class="text-muted small mb-4">Use a password nobody else at the shop knows.</p>

                            @if (session('status') === 'password-updated')
                                <div class="alert alert-success py-2 small" role="status">Your password was changed.</div>
                            @endif

                            <form method="POST" action="{{ route('password.update') }}">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold small">Current password</label>
                                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                                           class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif">
                                    @foreach($errors->updatePassword->get('current_password') as $message)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @endforeach
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold small">New password</label>
                                    <input id="password" name="password" type="password" autocomplete="new-password"
                                           class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif">
                                    @foreach($errors->updatePassword->get('password') as $message)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @endforeach
                                </div>

                                <div class="mb-4">
                                    <label for="password_confirmation" class="form-label fw-semibold small">Repeat new password</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                                           class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif">
                                    @foreach($errors->updatePassword->get('password_confirmation') as $message)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @endforeach
                                </div>

                                <button type="submit" class="btn btn-primary px-4">Change password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mt-4 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                To remove a staff account, an admin can do it under <strong>User Management → Users</strong>.
            </p>
        </div>
    </div>
</div>
@endsection
