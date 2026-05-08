<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>EDUC PHARMACY - {{ __('Authentication') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-brand-light">
    <div class="auth-container d-flex align-items-center justify-content-center min-vh-100 py-5">
        <div class="auth-card shadow-lg rounded-4 overflow-hidden bg-white">
            <div class="row g-0">
                <!-- Branding Side (Hidden on Mobile) -->
                <div class="col-lg-5 d-none d-lg-flex bg-brand-primary p-5 align-items-center justify-content-center text-center text-white flex-column">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC PHARMACY" class="auth-logo mb-4 rounded-circle bg-white p-2">
                    <h2 class="fw-bold mb-3 text-uppercase">EDUC PHARMACY</h2>
                    <p class="opacity-75">Your trusted partner in pharmacy management and healthcare efficiency.</p>
                </div>
                
                <!-- Form Side -->
                <div class="col-lg-7 p-4 p-md-5">
                    <div class="text-center mb-4 d-lg-none">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC PHARMACY" class="auth-logo-sm mb-3 rounded-circle shadow-sm">
                        <h3 class="fw-bold text-brand-primary text-uppercase">EDUC PHARMACY</h3>
                    </div>
                    
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --brand-primary: #1e40af;
            --brand-secondary: #1e3a8a;
            --brand-light: #f8fafc;
            --brand-accent: #8ec63f;
            --brand-bg: #f1f5f9;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--brand-bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(142, 198, 63, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(30, 64, 175, 0.05) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(142, 198, 63, 0.05) 0px, transparent 50%);
        }

        .auth-container {
            width: 100%;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card {
            width: 100%;
            max-width: 950px;
            margin: 0 20px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .bg-brand-primary {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
            position: relative;
            overflow: hidden;
        }

        .text-brand-primary {
            color: var(--brand-primary);
        }

        .auth-logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
            border: 3px solid var(--brand-accent);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1;
        }

        .auth-logo:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .auth-logo-sm {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border: 2px solid var(--brand-accent);
            z-index: 1;
        }

        /* Override Laravel Breeze / Component Defaults for the Brand */
        .form-control:focus {
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 0.25rem rgba(142, 198, 63, 0.1);
        }

        button.btn-primary, .x-primary-button {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: white !important;
        }

        button.btn-primary:hover, .x-primary-button:hover {
            background-color: var(--brand-secondary) !important;
            border-color: var(--brand-secondary) !important;
        }

        a {
            color: var(--brand-primary);
            text-decoration: none;
            transition: all 0.2s;
        }

        a:hover {
            color: var(--brand-accent);
            text-decoration: underline;
        }

        @media (max-width: 991.98px) {
            .auth-card {
                max-width: 500px;
            }
        }
    </style>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
