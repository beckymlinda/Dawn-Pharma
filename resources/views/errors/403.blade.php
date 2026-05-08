<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - EDUC PHARMACY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <style>
        :root {
            --brand-primary: #1e40af;
            --brand-secondary: #1e3a8a;
            --brand-accent: #8ec63f;
        }
        body {
            background-color: #f1f5f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
        }
        .error-card {
            max-width: 500px;
            width: 90%;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border-top: 5px solid var(--brand-accent);
        }
        .error-icon {
            font-size: 5rem;
            color: #475569;
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
            box-shadow: 0 10px 15px -3px rgba(30, 64, 175, 0.3);
        }
        .logo-img {
            height: 60px;
            margin-bottom: 2rem;
            border: 2px solid var(--brand-accent);
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC PHARMACY" class="logo-img">
        <div class="error-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h1 class="fw-bold mb-3" style="color: var(--brand-primary)">403</h1>
        <h3 class="h5 mb-3">Access Denied</h3>
        <p class="text-muted mb-4">
            You don't have permission to access this area. If you believe this is an error, please contact your administrator.
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
