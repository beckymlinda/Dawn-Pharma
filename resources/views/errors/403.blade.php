<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - {{ setting('system_name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="icon" href="{{ brand_favicon_url() }}">
    <style>
        :root {
            --brand-primary: {{ $settings['primary_color'] ?? '#004d3d' }};
            --brand-secondary: {{ $settings['secondary_color'] ?? '#006b54' }};
        }
        body {
            background-color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .error-card {
            max-width: 500px;
            width: 90%;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .error-icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1.5rem;
        }
        .btn-brand {
            background-color: var(--brand-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-brand:hover {
            background-color: var(--brand-secondary);
            color: white;
            transform: translateY(-2px);
        }
        .logo-img {
            height: 60px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <img src="{{ brand_logo_url() }}" alt="{{ setting('system_name') }}" class="logo-img">
        <div class="error-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h1 class="fw-bold mb-3" style="color: var(--brand-primary)">403</h1>
        <h3 class="h5 mb-3">Access Denied</h3>
        <p class="text-muted mb-4">
            You do not have permission to access this page. Please contact your administrator if you believe this is an error.
        </p>
        <div class="d-grid gap-2">
            <a href="{{ url('/') }}" class="btn btn-brand">
                <i class="bi bi-house me-2"></i> Go to Dashboard
            </a>
            <button onclick="window.history.back()" class="btn btn-outline-secondary border-0">
                <i class="bi bi-arrow-left me-2"></i> Go Back
            </button>
        </div>
    </div>
</body>
</html>
