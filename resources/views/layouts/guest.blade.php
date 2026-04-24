<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>EDUC Pharmacy - {{ __('Authentication') }}</title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
        
        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- AOS Animation Library -->
        <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    </head>
    <body class="font-sans antialiased">
        <div class="login-container">
            <!-- Background Elements -->
            <div class="bg-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
            
            <!-- Main Content -->
            <div class="login-wrapper">
                <!-- Left Side - Branding -->
                <div class="login-branding" data-aos="fade-right">
                    <div class="brand-content">
                        <div class="logo-section">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC Pharmacy" class="brand-logo">
                        </div>
                        
                        <h1 class="brand-title">Welcome Back</h1>
                        <p class="brand-subtitle">
                            Sign in to access your pharmacy management system and streamline your operations.
                        </p>
                        
                        <div class="brand-features">
                            <div class="brand-feature">
                                <i class="fas fa-shield-alt"></i>
                                <span>Secure Login</span>
                            </div>
                            <div class="brand-feature">
                                <i class="fas fa-clock"></i>
                                <span>24/7 Access</span>
                            </div>
                            <div class="brand-feature">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Mobile Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="login-form-section" data-aos="fade-left">
                    <div class="login-form-container">
                        <div class="form-header">
                            <h2>Sign In</h2>
                            <p>Enter your credentials to access the system</p>
                        </div>
                        
                        <!-- Session Status -->
                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="login-form">
                            @csrf

                            <!-- Email Address -->
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>
                                    Email Address
                                </label>
                                <input id="email" 
                                       class="form-control" 
                                       type="email" 
                                       name="email" 
                                       :value="old('email')" 
                                       required 
                                       autofocus 
                                       autocomplete="username"
                                       placeholder="Enter your email">
                                <x-input-error :messages="$errors->get('email')" class="error-message" />
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>
                                    Password
                                </label>
                                <div class="password-input-group">
                                    <input id="password" 
                                           class="form-control" 
                                           type="password"
                                           name="password"
                                           required 
                                           autocomplete="current-password"
                                           placeholder="Enter your password">
                                    <button type="button" class="password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="error-message" />
                            </div>

                            <!-- Remember Me -->
                            <div class="form-options">
                                <label class="checkbox-label">
                                    <input id="remember_me" type="checkbox" name="remember">
                                    <span class="checkmark"></span>
                                    Remember me
                                </label>
                                
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="forgot-password">
                                        Forgot your password?
                                    </a>
                                @endif
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="login-button">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                            
                            <!-- Register Link -->
                            <div class="register-link">
                                <p>Don't have an account? 
                                    <a href="{{ route('register') }}">Get Started</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', 'Poppins', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                overflow-x: hidden;
            }

            .login-container {
                position: relative;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }

            /* Background Shapes */
            .bg-shapes {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                overflow: hidden;
                z-index: 1;
            }

            .shape {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.1);
                animation: float 6s ease-in-out infinite;
            }

            .shape-1 {
                width: 300px;
                height: 300px;
                top: -150px;
                left: -150px;
                animation-delay: 0s;
            }

            .shape-2 {
                width: 200px;
                height: 200px;
                bottom: -100px;
                right: -100px;
                animation-delay: 2s;
            }

            .shape-3 {
                width: 150px;
                height: 150px;
                top: 50%;
                left: 10%;
                animation-delay: 4s;
            }

            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-20px) rotate(180deg); }
            }

            /* Login Wrapper */
            .login-wrapper {
                position: relative;
                z-index: 2;
                display: flex;
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                overflow: hidden;
                max-width: 1000px;
                width: 100%;
                min-height: 600px;
            }

            /* Branding Section */
            .login-branding {
                flex: 1;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 3rem;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
            }

            .brand-content {
                text-align: center;
                color: white;
            }

            .logo-section {
                margin-bottom: 2rem;
            }

            .brand-logo {
                height: 80px;
                width: auto;
                filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
                animation: float 3s ease-in-out infinite;
            }

            .brand-title {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .brand-subtitle {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                opacity: 0.9;
                line-height: 1.6;
            }

            .brand-features {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .brand-feature {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
                font-size: 0.95rem;
            }

            .brand-feature i {
                color: #f5576c;
                font-size: 1.2rem;
            }

            /* Form Section */
            .login-form-section {
                flex: 1;
                padding: 3rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .login-form-container {
                width: 100%;
                max-width: 400px;
            }

            .form-header {
                text-align: center;
                margin-bottom: 2rem;
            }

            .form-header h2 {
                font-size: 2rem;
                font-weight: 700;
                color: #333;
                margin-bottom: 0.5rem;
            }

            .form-header p {
                color: #666;
                font-size: 0.95rem;
            }

            /* Form Styles */
            .login-form {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .form-group {
                display: flex;
                flex-direction: column;
            }

            .form-label {
                font-weight: 600;
                color: #333;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
            }

            .form-control {
                padding: 0.875rem 1rem;
                border: 2px solid #e1e5e9;
                border-radius: 10px;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                background: #f8f9fa;
            }

            .form-control:focus {
                outline: none;
                border-color: #667eea;
                background: white;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }

            .password-input-group {
                position: relative;
            }

            .password-toggle {
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #666;
                cursor: pointer;
                font-size: 0.9rem;
            }

            .password-toggle:hover {
                color: #667eea;
            }

            .form-options {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }

            .checkbox-label {
                display: flex;
                align-items: center;
                cursor: pointer;
                font-size: 0.9rem;
                color: #666;
            }

            .checkbox-label input[type="checkbox"] {
                display: none;
            }

            .checkmark {
                width: 18px;
                height: 18px;
                border: 2px solid #e1e5e9;
                border-radius: 4px;
                margin-right: 0.5rem;
                position: relative;
                transition: all 0.3s ease;
            }

            .checkbox-label input[type="checkbox"]:checked + .checkmark {
                background: #667eea;
                border-color: #667eea;
            }

            .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
                content: 'i';
                position: absolute;
                top: -2px;
                left: 3px;
                color: white;
                font-size: 12px;
                font-weight: bold;
            }

            .forgot-password {
                color: #667eea;
                text-decoration: none;
                font-size: 0.9rem;
                transition: color 0.3s ease;
            }

            .forgot-password:hover {
                color: #764ba2;
            }

            .login-button {
                background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                padding: 1rem;
                border-radius: 10px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            }

            .login-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            }

            .register-link {
                text-align: center;
                margin-top: 1.5rem;
            }

            .register-link p {
                color: #666;
                font-size: 0.9rem;
            }

            .register-link a {
                color: #667eea;
                text-decoration: none;
                font-weight: 600;
                transition: color 0.3s ease;
            }

            .register-link a:hover {
                color: #764ba2;
            }

            .error-message {
                color: #e74c3c;
                font-size: 0.85rem;
                margin-top: 0.5rem;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .login-wrapper {
                    flex-direction: column;
                    margin: 1rem;
                }

                .login-branding {
                    padding: 2rem;
                }

                .brand-title {
                    font-size: 2rem;
                }

                .login-form-section {
                    padding: 2rem;
                }

                .form-options {
                    flex-direction: column;
                    gap: 1rem;
                    align-items: flex-start;
                }

                .brand-features {
                    flex-direction: row;
                    flex-wrap: wrap;
                    justify-content: center;
                    gap: 1.5rem;
                }
            }

            @media (max-width: 480px) {
                .login-container {
                    padding: 1rem;
                }

                .brand-title {
                    font-size: 1.5rem;
                }

                .form-header h2 {
                    font-size: 1.5rem;
                }
            }
        </style>

        <script>
            function togglePassword() {
                const passwordInput = document.getElementById('password');
                const toggleIcon = document.getElementById('passwordToggleIcon');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            }

            // AOS Animation
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof AOS !== 'undefined') {
                    AOS.init({
                        duration: 1000,
                        once: true,
                        offset: 100
                    });
                }
            });
        </script>

        <!-- AOS Animation Script -->
        <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    </body>
</html>
