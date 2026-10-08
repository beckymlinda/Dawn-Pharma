@extends('layouts.app')

@section('title', 'Settings · Shop details')

@section('content')
<div class="container-fluid">
    @include('settings._tabs')

    <form action="{{ route('settings.update') }}" method="POST" id="settingsForm">
        @csrf
        <input type="hidden" name="section" value="shop">

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="settings-card">
                    <div class="settings-card-head">
                        <h6><i class="bi bi-shop me-2 text-primary"></i>Shop details</h6>
                        <p class="text-muted small mb-0">Shown on receipts, Z reports and the login page.</p>
                    </div>
                    <div class="settings-card-body">
                        <div class="mb-3">
                            <label class="settings-label" for="system_name">Shop name <span class="text-danger">*</span></label>
                            <input type="text" name="system_name" id="system_name" class="form-control" data-preview="name"
                                   value="{{ old('system_name', $settings['system_name'] ?? '') }}" placeholder="e.g. Area 25 Pharmacy" required>
                        </div>
                        <div class="mb-3">
                            <label class="settings-label" for="location">Location / address</label>
                            <input type="text" name="location" id="location" class="form-control" data-preview="location"
                                   value="{{ old('location', $settings['location'] ?? '') }}" placeholder="e.g. Area 25, Lilongwe">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="settings-label" for="telephone">Phone</label>
                                <input type="text" name="telephone" id="telephone" class="form-control" data-preview="telephone"
                                       value="{{ old('telephone', $settings['telephone'] ?? '') }}" placeholder="e.g. 0991 234 567">
                            </div>
                            <div class="col-sm-6">
                                <label class="settings-label" for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control"
                                       value="{{ old('email', $settings['email'] ?? '') }}" placeholder="shop@example.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="settings-label" for="tax_number">TPIN / VAT number</label>
                            <input type="text" name="tax_number" id="tax_number" class="form-control" data-preview="tax"
                                   value="{{ old('tax_number', $settings['tax_number'] ?? '') }}" placeholder="Printed on receipts when filled in">
                        </div>
                        <div class="mb-4">
                            <label class="settings-label" for="receipt_footer">Message at the bottom of receipts</label>
                            <input type="text" name="receipt_footer" id="receipt_footer" class="form-control" data-preview="footer"
                                   value="{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}" placeholder="e.g. Thank you, get well soon!">
                        </div>
                        <button type="submit" class="btn btn-primary px-4" id="saveBtn"><i class="bi bi-check2 me-1"></i> Save shop details</button>
                    </div>
                </div>
            </div>

            {{-- How the top of a receipt will look --}}
            <div class="col-lg-5">
                <div class="settings-card">
                    <div class="settings-card-head"><h6><i class="bi bi-receipt me-2 text-primary"></i>Receipt preview</h6></div>
                    <div class="settings-card-body">
                        <div class="receipt-preview">
                            <div class="fw-bold fs-6" id="pvName">{{ $settings['system_name'] ?? '' }}</div>
                            <div id="pvLocation">{{ $settings['location'] ?? '' }}</div>
                            <div id="pvTelephone">{{ !empty($settings['telephone']) ? 'Tel: ' . $settings['telephone'] : '' }}</div>
                            <div id="pvTax">{{ !empty($settings['tax_number']) ? 'TPIN: ' . $settings['tax_number'] : '' }}</div>
                            <div class="receipt-rule"></div>
                            <div class="d-flex justify-content-between"><span>Panadol 500mg x2</span><span>2,000.00</span></div>
                            <div class="d-flex justify-content-between fw-bold"><span>TOTAL</span><span>MWK 2,000.00</span></div>
                            <div class="receipt-rule"></div>
                            <div id="pvFooter">{{ $settings['receipt_footer'] ?? '' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .receipt-preview { font-family: ui-monospace, Consolas, monospace; font-size: .8rem; text-align: center; background: #fffdf5; border: 1px dashed #e2e8f0; border-radius: 10px; padding: 1rem; color: #1e293b; }
    .receipt-preview .d-flex { text-align: left; }
    .receipt-rule { border-top: 1px dashed #94a3b8; margin: .6rem 0; }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var targets = { name: ['pvName', ''], location: ['pvLocation', ''], telephone: ['pvTelephone', 'Tel: '], tax: ['pvTax', 'TPIN: '], footer: ['pvFooter', ''] };
    document.querySelectorAll('[data-preview]').forEach(function (input) {
        input.addEventListener('input', function () {
            var t = targets[input.dataset.preview];
            document.getElementById(t[0]).textContent = input.value ? t[1] + input.value : '';
        });
    });
    document.getElementById('settingsForm').addEventListener('submit', function () {
        var btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
    });
});
</script>
@endsection
