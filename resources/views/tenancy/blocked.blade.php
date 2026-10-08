<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tenant->name }} – unavailable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
<div class="container" style="max-width: 520px;">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5 text-center">
            <img src="{{ brand_logo_url() }}" alt="" width="64" height="64" class="rounded-3 mb-3">
            <h1 class="h4 fw-bold">{{ $tenant->name }} is unavailable</h1>
            <p class="text-muted">{{ $message }}</p>
            <p class="small mb-2">Your data is safe. Please contact your system provider to restore access.</p>
            @php($contact = config('tenancy.billing_contact'))
            <p class="small mb-4">
                <strong>{{ $contact['name'] }}</strong>
                @if($contact['phone'])<br><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>@endif
                @if($contact['email'])<br><a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>@endif
                @if($contact['pay_to'])<br>Pay to: {{ $contact['pay_to'] }} (reference: {{ $tenant->slug }})@endif
            </p>
            @auth
                @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin'))
                    <a href="{{ route('billing.index') }}" class="btn btn-success mb-3"><i class="bi bi-upload"></i> Pay &amp; upload proof of payment</a>
                @else
                    <p class="small text-muted">Ask your shop owner to renew the subscription.</p>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm">Log out</button>
                </form>
            @endauth
        </div>
    </div>
</div>
</body>
</html>
