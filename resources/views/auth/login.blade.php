<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="fw-bold">Welcome Back</h4>
        <p class="text-muted small">Please enter your credentials to access your account</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Shop: asked only when this browser has not opened a shop link -->
        @if(tenant())
            <p class="small text-muted text-center mb-3">
                Logging in to <strong>{{ tenant()->name }}</strong>.
                <a href="#" onclick="document.getElementById('shopField').classList.remove('d-none'); this.parentElement.classList.add('d-none'); return false;">Not your shop?</a>
            </p>
        @endif
        <div class="mb-3 {{ tenant() && !$errors->has('shop') ? 'd-none' : '' }}" id="shopField">
            <label for="shop" class="form-label small fw-bold text-muted">Shop code</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-store text-muted"></i></span>
                <input id="shop" class="form-control border-start-0 bg-light" type="text" name="shop" value="{{ old('shop') }}"
                       {{ tenant() ? '' : 'required autofocus' }} autocomplete="organization" autocapitalize="none" spellcheck="false" placeholder="e.g. area25" />
            </div>
            <div class="form-text small">The last part of your shop's link, e.g. {{ url('/') }}/<strong>area25</strong></div>
            <x-input-error :messages="$errors->get('shop')" class="mt-2 text-danger small" />
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small fw-bold text-muted">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                <input id="email" class="form-control border-start-0 bg-light" type="email" name="email" value="{{ old('email') }}" required {{ tenant() ? 'autofocus' : '' }} autocomplete="username" placeholder="name@example.com" />
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
        
        
    </form>
    
    <style>
        .btn-brand {
            background-color: var(--brand-primary, #004d3d);
            border-color: var(--brand-primary, #004d3d);
            color: white;
        }
        .btn-brand:hover {
            background-color: var(--brand-secondary, #006b54);
            border-color: var(--brand-secondary, #006b54);
            color: white;
        }
        .text-brand-primary {
            color: var(--brand-primary, #004d3d);
        }
    </style>
</x-guest-layout>
