<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="fw-bold">Welcome Back</h4>
        <p class="text-muted small">Please enter your credentials to access your account</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small fw-bold text-muted">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                <input id="email" class="form-control border-start-0 bg-light" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@example.com" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger small" />
        </div>

        <!-- Password -->
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label for="password" class="form-label small fw-bold text-muted">Password</label>
                @if (Route::has('password.request'))
                    <a class="small text-brand-primary" href="{{ route('password.request') }}">
                        Forgot?
                    </a>
                @endif
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                <input id="password" class="form-control border-start-0 bg-light" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger small" />
        </div>

        <!-- Remember Me -->
        <div class="mb-4">
            <div class="form-check">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label for="remember_me" class="form-check-label small text-muted">Remember me</label>
            </div>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-brand py-2 fw-bold shadow-sm rounded-pill">
                Sign In
            </button>
        </div>
        
        <div class="text-center">
            <p class="small text-muted mb-0">Don't have an account? <a href="{{ route('register') }}" class="fw-bold text-brand-primary">Register</a></p>
        </div>
    </form>
    
    <style>
        .btn-brand {
            background-color: #1e40af;
            border-color: #1e40af;
            color: white;
        }
        .btn-brand:hover {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
            color: white;
        }
        .text-brand-primary {
            color: #1e40af;
        }
        .form-control:focus {
            border-color: #8ec63f !important;
            box-shadow: 0 0 0 0.25rem rgba(142, 198, 63, 0.1) !important;
        }
    </style>
</x-guest-layout>
