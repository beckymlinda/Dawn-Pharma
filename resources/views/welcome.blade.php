<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings['system_name'] ?? 'CNC Health Care' }} - Professional Pharmacy Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ isset($settings['favicon']) ? asset($settings['favicon']) : asset('assets/images/favicon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS for spacing/grid -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="welcome-container d-flex align-items-center justify-content-center min-vh-100">
        <div class="content-card text-center p-5 shadow-lg rounded-5">
            <div class="logo-wrapper mb-4">
                <img src="{{ isset($settings['logo']) ? asset($settings['logo']) : asset('assets/images/logo.jpeg') }}" alt="{{ $settings['system_name'] ?? 'CNC Health Care' }}" class="main-logo shadow-sm rounded-circle p-2 bg-white">
            </div>
            
            <h1 class="brand-title mb-2">{{ $settings['system_name'] ?? 'CNC Health Care' }}</h1>
            <p class="brand-tagline mb-5 text-muted">Advanced Pharmacy Management & Care</p>
            
            <div class="row g-3 justify-content-center mb-5">
                <div class="col-12 col-sm-8 col-md-6">
                    <a href="{{ route('login') }}" class="btn btn-brand btn-lg w-100 py-3 shadow-sm rounded-pill">
                        <i class="fas fa-sign-in-alt me-2"></i> Login to System
                    </a>
                </div>
            </div>
            
            <div class="footer-badges d-flex justify-content-center gap-3 flex-wrap">
                <span class="badge-item"><i class="fas fa-shield-alt text-brand me-1"></i> Secure</span>
                <span class="badge-item"><i class="fas fa-bolt text-brand me-1"></i> Efficient</span>
                <span class="badge-item"><i class="fas fa-chart-line text-brand me-1"></i> Smart Analytics</span>
            </div>
        </div>
    </div>

    <style>
        :root {
            --brand-primary: {{ $settings['primary_color'] ?? '#004d3d' }};
            --brand-secondary: {{ $settings['secondary_color'] ?? '#006b54' }};
            --brand-light: #fff9e6;
            --brand-accent: #22c55e;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--brand-light);
            background-image: radial-gradient(circle at 20% 20%, rgba(0, 77, 61, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 80% 80%, rgba(0, 77, 61, 0.05) 0%, transparent 40%);
            color: #333;
        }

        .content-card {
            background: white;
            max-width: 600px;
            width: 90%;
            border: 1px solid rgba(0, 77, 61, 0.1);
            position: relative;
        }

        .main-logo {
            height: 120px;
            width: 120px;
            object-fit: contain;
        }

        .brand-title {
            font-weight: 800;
            color: var(--brand-primary);
            font-size: 2.5rem;
            letter-spacing: -1px;
        }

        .brand-tagline {
            font-weight: 500;
            font-size: 1.1rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .btn-brand {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-brand:hover {
            background-color: var(--brand-secondary);
            border-color: var(--brand-secondary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 77, 61, 0.3) !important;
        }

        .btn-outline-brand {
            border: 2px solid var(--brand-primary);
            color: var(--brand-primary);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-brand:hover {
            background-color: var(--brand-primary);
            color: white;
            transform: translateY(-3px);
        }

        .text-brand {
            color: var(--brand-primary);
        }

        .badge-item {
            font-size: 0.85rem;
            font-weight: 600;
            color: #666;
            background: #f8fbf4;
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid rgba(0, 77, 61, 0.1);
        }

        @media (max-width: 576px) {
            .brand-title {
                font-size: 2rem;
            }
            .content-card {
                padding: 2rem !important;
            }
        }
    </style>
</body>
</html>