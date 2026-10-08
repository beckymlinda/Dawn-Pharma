<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Server setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 720px;">
    <h1 class="h3 fw-bold mb-1">Server setup</h1>
    <p class="text-muted">For hosting without a terminal. Remove <code>PLATFORM_SETUP_TOKEN</code> from <code>.env</code> when you are done.</p>

    @if($output)
        <pre class="bg-dark text-light p-3 rounded small" style="white-space: pre-wrap;">{{ $output }}</pre>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 fw-bold">1. Database</h2>
            <p class="mb-3">{{ $pending ? "$pending migration(s) waiting to run." : 'All migrations have run.' }}</p>
            <form method="POST" action="/setup/migrate?token={{ urlencode($token) }}" class="d-inline">
                @csrf
                <button class="btn btn-primary btn-sm" @disabled(!$pending)>Run new migrations</button>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 fw-bold">2. First platform admin</h2>
            @if(!$migrated)
                <p class="text-muted mb-0">Run the migrations first.</p>
            @elseif($hasPlatformAdmin)
                <p class="mb-0">A platform admin exists. Log in at <a href="{{ url('/platform/login') }}">{{ url('/platform/login') }}</a>.</p>
            @else
                <form method="POST" action="/setup/admin?token={{ urlencode($token) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6"><input class="form-control" name="name" placeholder="Your name" value="{{ old('name') }}" required></div>
                        <div class="col-md-6"><input class="form-control" type="email" name="email" placeholder="Email" value="{{ old('email') }}" required></div>
                        <div class="col-md-6"><input class="form-control" type="password" name="password" placeholder="Password (10+ characters)" required></div>
                        <div class="col-md-6"><input class="form-control" type="password" name="password_confirmation" placeholder="Repeat password" required></div>
                    </div>
                    @if($errors->any())<div class="text-danger small mt-2">{{ implode(' ', $errors->all()) }}</div>@endif
                    <button class="btn btn-primary btn-sm mt-3">Create platform admin</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 fw-bold">3. Tools</h2>
            <form method="POST" action="/setup/clear-cache?token={{ urlencode($token) }}" class="d-inline">@csrf<button class="btn btn-outline-secondary btn-sm">Clear caches</button></form>
            <form method="POST" action="/setup/storage-link?token={{ urlencode($token) }}" class="d-inline">@csrf<button class="btn btn-outline-secondary btn-sm">Re-link uploaded files</button></form>
        </div>
    </div>
</div>
</body>
</html>
