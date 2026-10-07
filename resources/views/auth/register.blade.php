<x-guest-layout>
    <div class="registration-header mb-4 text-center">
        <h3 class="fw-bold text-dark mb-1">Create Account</h3>
        <p class="text-muted small px-3">Join CNC Health Care pharmacy management system and start managing your business today.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="registration-form px-1 px-md-2">
        @csrf

        <!-- Name -->
        <div class="form-floating mb-3">
            <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe">
            <label for="name" class="text-muted"><i class="fas fa-user me-2"></i>Full Name</label>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="form-floating mb-3">
            <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                value="{{ old('email') }}" required autocomplete="username" placeholder="name@example.com">
            <label for="email" class="text-muted"><i class="fas fa-envelope me-2"></i>Email Address</label>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-2">
            <!-- Password -->
            <div class="col-md-6 mb-3">
                <div class="form-floating">
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                        required autocomplete="new-password" placeholder="••••••••">
                    <label for="password" class="text-muted"><i class="fas fa-lock me-2"></i>Password</label>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="col-md-6 mb-3">
                <div class="form-floating">
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" 
                        required autocomplete="new-password" placeholder="••••••••">
                    <label for="password_confirmation" class="text-muted"><i class="fas fa-check-double me-2"></i>Confirm</label>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="form-check small">
                <input class="form-check-input" type="checkbox" value="" id="terms" required>
                <label class="form-check-label text-muted" for="terms">
                    I agree to the <a href="#" class="text-brand-primary fw-semibold">Terms of Service</a> and <a href="#" class="text-brand-primary fw-semibold">Privacy Policy</a>
                </label>
            </div>
        </div>

        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-brand btn-lg py-3 fw-bold shadow-sm rounded-3 transition-all">
                Create My Account <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>

        <div class="text-center mt-2">
            <p class="text-muted small">
                Already have an account? 
                <a href="{{ route('login') }}" class="fw-bold text-brand-primary ms-1">Sign In Now</a>
            </p>
        </div>
    </form>

    <style>
        .form-floating > .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding-left: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }

        .form-floating > .form-control:focus {
            background-color: #ffffff;
            border-color: #004d3d;
            box-shadow: 0 0 0 4px rgba(0, 77, 61, 0.1);
        }

        .form-floating > label {
            padding-left: 1rem;
        }

        .btn-brand {
            background: linear-gradient(135deg, #004d3d 0%, #006b54 100%);
            border: none;
            color: white;
            letter-spacing: 0.5px;
        }

        .btn-brand:hover {
            background: linear-gradient(135deg, #006b54 0%, #004d3d 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 77, 61, 0.2);
            color: white;
        }

        .btn-brand:active {
            transform: translateY(0);
        }

        .text-brand-primary {
            color: #004d3d;
            text-decoration: none;
        }

        .text-brand-primary:hover {
            color: #006b54;
            text-decoration: underline;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        @media (max-width: 576px) {
            .registration-header h3 {
                font-size: 1.5rem;
            }
            .form-floating > label {
                font-size: 0.85rem;
            }
        }
    </style>
</x-guest-layout>

