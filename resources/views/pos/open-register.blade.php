@extends('layouts.app')
@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">
        <h1 class="h4 fw-bold mb-4">Open Cash Register</h1>
        <div class="card border mx-auto" style="max-width:640px">
          <div class="card-body p-5">
            <div class="mb-4">
              <label class="form-label fw-semibold">Cash in hand:*</label>
              <input type="number" class="form-control" placeholder="Enter amount" id="cashAmount">
            </div>
            <div class="text-end">
              <button class="btn btn-primary fw-semibold" onclick="openRegister()">Open Register</button>
            </div>
          </div>
        </div>
      </main>
      <footer class="app-footer">{{ setting('system_name') }} | Copyright © {{ date('Y') }} All rights reserved.</footer>
    </div>
  </div>

  <script>
    async function openRegister() {
      const amt = parseFloat(document.getElementById('cashAmount').value);
      if (!amt && amt !== 0) {
        alert('Please enter cash in hand amount');
        return;
      }

      try {
        const res = await fetch('{{ route('pos.register.open') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ cash_in_hand: amt })
        });

        if (!res.ok) {
          const txt = await res.text();
          throw new Error(txt || 'Failed to open register');
        }

        // After opening, go to POS screen which will detect open register
        window.location.href = '{{ route('pos.create') }}';
      } catch (e) {
        console.error(e);
        alert('Error opening register: ' + e.message);
      }
    }
  </script>

@endsection
 