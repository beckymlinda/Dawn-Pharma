<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EDUC PHARMACY - Professional Pharmacy Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS for spacing/grid -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="welcome-container d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="content-card text-center p-4 p-md-5 shadow-lg rounded-5">
            <div class="logo-wrapper mb-4">
                <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC PHARMACY" class="main-logo shadow-sm rounded-circle p-2 bg-white">
            </div>
            
            <h1 class="brand-title mb-2">EDUC PHARMACY</h1>
            <p class="brand-tagline mb-5">Advanced Pharmacy Management & Care</p>
            
            <div class="row g-3 justify-content-center mb-5">
                <div class="col-12 col-md-10 col-lg-8">
                    <a href="{{ route('login') }}" class="btn btn-login btn-lg w-100 py-3 shadow-sm rounded-pill mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Access Portal
                    </a>
                </div>
            </div>
            
            <div class="footer-badges d-flex justify-content-center gap-2 flex-wrap">
                <span class="badge-item"><i class="fas fa-shield-alt text-green me-1"></i> Secure</span>
                <span class="badge-item"><i class="fas fa-bolt text-green me-1"></i> Efficient</span>
                <span class="badge-item"><i class="fas fa-chart-line text-green me-1"></i> Analytics</span>
            </div>
            
            <div class="mt-5 pt-3 border-top x-small text-muted opacity-50">
                &copy; {{ date('Y') }} EDUC PHARMACY. All rights reserved.
            </div>
        </div>
    </div>

    <style>
        :root {
            --brand-blue: #1e40af;
            --brand-blue-hover: #1e3a8a;
            --brand-green: #8ec63f;
            --brand-light: #f0fdf4;
            --brand-bg: #f8fafc;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--brand-bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(30, 64, 175, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(142, 198, 63, 0.05) 0%, transparent 50%);
            color: #334155;
            margin: 0;
            overflow-x: hidden;
        }

        .welcome-container {
            position: relative;
        }

        .welcome-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--brand-blue), var(--brand-green));
            z-index: 10;
        }

        .content-card {
            background: white;
            max-width: 550px;
            width: 100%;
            border: 1px solid rgba(142, 198, 63, 0.1);
            position: relative;
            transition: transform 0.3s ease;
        }

        .main-logo {
            height: 100px;
            width: 100px;
            object-fit: contain;
            border: 2px solid var(--brand-green);
        }

        .brand-title {
            font-weight: 800;
            color: var(--brand-blue);
            font-size: 2.2rem;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .brand-tagline {
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 1px;
            color: #64748b;
            text-transform: uppercase;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--brand-blue) 0%, var(--brand-blue-hover) 100%);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 20px rgba(30, 64, 175, 0.15) !important;
        }

        .btn-login:hover {
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(30, 64, 175, 0.25) !important;
        }

        .text-green {
            color: var(--brand-green);
        }

        .badge-item {
            font-size: 0.8rem;
            font-weight: 700;
            color: #475569;
            background: #f8fafc;
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(142, 198, 63, 0.2);
            text-transform: uppercase;
        }

        .x-small {
            font-size: 0.75rem;
        }

        @media (max-width: 576px) {
            .brand-title {
                font-size: 1.8rem;
            }
            .content-card {
                padding: 1.5rem !important;
                border: none;
                box-shadow: none !important;
                background: transparent;
            }
            body {
                background-color: white;
            }
        }
    </style>
</body>
</html>