<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Admin portal · Log in</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="min-height: 100vh; background: #0f172a;">
<div class="container" style="max-width: 400px;">
    <div class="card border-0 rounded-4 shadow">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 fw-bold mb-1">Admin portal</h1>
            <p class="text-muted small mb-4">For the system provider. Shop staff log in through their shop's link.</p>
            <form method="POST" action="{{ url('/platform/login') }}" data-submit-once>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password</label>
                    <input id="password" type="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember">Keep me logged in</label>
                </div>
                <button class="btn btn-dark w-100" data-sending-text="Logging in…">Log in</button>
            </form>
        </div>
    </div>
</div>
<script src="{{ asset('js/submit-once.js') }}?v={{ filemtime(public_path('js/submit-once.js')) }}"></script>
</body>
</html>
