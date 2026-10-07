@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">

            {{-- Page Header --}}
            <div class="d-flex align-items-center mb-4">
                <div class="settings-icon-wrap me-3">
                    <i class="bi bi-gear-fill"></i>
                </div>
                <div>
                    <h4 class="mb-0 fw-bold">System Settings</h4>
                    <small class="text-muted">Manage your business details, receipt, branding and theme colors</small>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0 ps-0" style="list-style:none;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
                @csrf

                <div class="row g-4">

                    {{-- ===== Basic Information Card ===== --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-building me-2"></i>Business Information
                                </h6>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">Business Name</label>
                                    <input type="text" name="system_name" class="form-control"
                                           value="{{ old('system_name', $settings['system_name'] ?? '') }}"
                                           placeholder="Enter business name" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">Location / Address</label>
                                    <input type="text" name="location" class="form-control"
                                           value="{{ old('location', $settings['location'] ?? '') }}"
                                           placeholder="e.g. Plot 12, City Centre, Lilongwe">
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small text-uppercase text-muted">Telephone Number</label>
                                        <input type="text" name="telephone" class="form-control"
                                               value="{{ old('telephone', $settings['telephone'] ?? '') }}"
                                               placeholder="e.g. +265 999 000 000">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold small text-uppercase text-muted">Email</label>
                                        <input type="email" name="email" class="form-control"
                                               value="{{ old('email', $settings['email'] ?? '') }}"
                                               placeholder="shop@example.com">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">TPIN / VAT Number</label>
                                    <input type="text" name="tax_number" class="form-control"
                                           value="{{ old('tax_number', $settings['tax_number'] ?? '') }}"
                                           placeholder="Printed on receipts when filled in">
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">Receipt Message</label>
                                    <input type="text" name="receipt_footer" class="form-control"
                                           value="{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}"
                                           placeholder="e.g. Thank you for your purchase!">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== Theme Colors Card ===== --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-palette-fill me-2"></i>Theme Colors
                                </h6>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small text-uppercase text-muted">Primary Color</label>
                                        <div class="color-picker-wrap">
                                            <input type="color" name="primary_color" id="primaryColor"
                                                   class="form-control form-control-color"
                                                   value="{{ $settings['primary_color'] ?? '#1E3A8A' }}">
                                            <span class="color-hex-label" id="primaryHex">{{ $settings['primary_color'] ?? '#1E3A8A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small text-uppercase text-muted">Secondary Color</label>
                                        <div class="color-picker-wrap">
                                            <input type="color" name="secondary_color" id="secondaryColor"
                                                   class="form-control form-control-color"
                                                   value="{{ $settings['secondary_color'] ?? '#10B981' }}">
                                            <span class="color-hex-label" id="secondaryHex">{{ $settings['secondary_color'] ?? '#10B981' }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Live Color Preview --}}
                                <div class="mt-4">
                                    <label class="form-label fw-semibold small text-uppercase text-muted">Preview</label>
                                    <div class="color-preview-bar" id="colorPreviewBar">
                                        <div class="preview-primary" id="previewPrimary"
                                             style="background: {{ $settings['primary_color'] ?? '#1E3A8A' }};">
                                            <span>Primary</span>
                                        </div>
                                        <div class="preview-secondary" id="previewSecondary"
                                             style="background: {{ $settings['secondary_color'] ?? '#10B981' }};">
                                            <span>Secondary</span>
                                        </div>
                                    </div>
                                    <div class="preview-navbar mt-2" id="previewNavbar"
                                         style="background: linear-gradient(90deg, {{ $settings['primary_color'] ?? '#1E3A8A' }} 0%, {{ $settings['secondary_color'] ?? '#10B981' }} 100%);">
                                        <i class="bi bi-list text-white me-2"></i>
                                        <span class="text-white small fw-semibold">Navbar Preview</span>
                                        <span class="text-white ms-auto small opacity-75">Admin User</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== Logo Upload Card ===== --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-image me-2"></i>System Logo
                                </h6>
                                <small class="text-muted">Used in sidebar, receipts, and login page</small>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="upload-zone" id="logoUploadZone">
                                    {{-- Current / Preview Image --}}
                                    <div class="preview-container mb-3" id="logoPreviewContainer">
                                        @if(brand_image_exists($settings['logo'] ?? null))
                                            <img src="{{ asset($settings['logo']) }}" alt="Current Logo" class="preview-image" id="logoPreview">
                                            <span class="preview-badge badge bg-success">Current</span>
                                        @else
                                            <div class="no-image-placeholder" id="logoPlaceholder">
                                                <i class="bi bi-image text-muted" style="font-size: 2.5rem;"></i>
                                                <p class="text-muted small mb-0 mt-2">No logo uploaded</p>
                                            </div>
                                            <img src="" alt="Logo Preview" class="preview-image d-none" id="logoPreview">
                                        @endif
                                    </div>

                                    {{-- Upload Input --}}
                                    <div class="upload-input-wrap">
                                        <label for="logoInput" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-cloud-arrow-up me-1"></i>
                                            {{ brand_image_exists($settings['logo'] ?? null) ? 'Change Logo' : 'Upload Logo' }}
                                        </label>
                                        <input type="file" name="logo" id="logoInput"
                                               class="d-none" accept="image/*">
                                        <small class="text-muted d-block mt-1 text-center">
                                            JPG, PNG, SVG, WEBP — Max 2MB
                                        </small>
                                    </div>

                                    {{-- File name display --}}
                                    <div class="selected-file d-none mt-2 text-center" id="logoFileName">
                                        <i class="bi bi-file-earmark-image text-success me-1"></i>
                                        <span class="small"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== Favicon Upload Card ===== --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-bookmark-star-fill me-2"></i>Favicon
                                </h6>
                                <small class="text-muted">Browser tab icon (recommended: 64×64px)</small>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <div class="upload-zone" id="faviconUploadZone">
                                    {{-- Current / Preview Image --}}
                                    <div class="preview-container preview-container-sm mb-3" id="faviconPreviewContainer">
                                        @if(brand_image_exists($settings['favicon'] ?? null))
                                            <img src="{{ asset($settings['favicon']) }}" alt="Current Favicon" class="preview-image preview-image-sm" id="faviconPreview">
                                            <span class="preview-badge badge bg-success">Current</span>
                                        @else
                                            <div class="no-image-placeholder no-image-placeholder-sm" id="faviconPlaceholder">
                                                <i class="bi bi-bookmark-star text-muted" style="font-size: 2rem;"></i>
                                                <p class="text-muted small mb-0 mt-1">No favicon</p>
                                            </div>
                                            <img src="" alt="Favicon Preview" class="preview-image preview-image-sm d-none" id="faviconPreview">
                                        @endif
                                    </div>

                                    {{-- Upload Input --}}
                                    <div class="upload-input-wrap">
                                        <label for="faviconInput" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="bi bi-cloud-arrow-up me-1"></i>
                                            {{ brand_image_exists($settings['favicon'] ?? null) ? 'Change Favicon' : 'Upload Favicon' }}
                                        </label>
                                        <input type="file" name="favicon" id="faviconInput"
                                               class="d-none" accept="image/*">
                                        <small class="text-muted d-block mt-1 text-center">
                                            ICO, PNG, SVG — Max 1MB
                                        </small>
                                    </div>

                                    {{-- File name display --}}
                                    <div class="selected-file d-none mt-2 text-center" id="faviconFileName">
                                        <i class="bi bi-file-earmark-image text-success me-1"></i>
                                        <span class="small"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Save Button --}}
                <div class="text-end mt-4 mb-3">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" id="saveBtn">
                        <i class="bi bi-save me-2"></i>Save Settings
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
    /* Settings Icon Header */
    .settings-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--theme-primary, #1E3A8A), var(--theme-secondary, #10B981));
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    /* Card Styling */
    .card {
        border-radius: 12px;
        transition: box-shadow 0.2s ease;
    }
    .card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,0.08) !important;
    }

    /* Color Picker */
    .color-picker-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .color-picker-wrap .form-control-color {
        width: 50px;
        height: 42px;
        padding: 4px;
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        flex-shrink: 0;
    }
    .color-hex-label {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Color Preview */
    .color-preview-bar {
        display: flex;
        border-radius: 8px;
        overflow: hidden;
        height: 40px;
    }
    .preview-primary, .preview-secondary {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .preview-navbar {
        border-radius: 8px;
        padding: 8px 14px;
        display: flex;
        align-items: center;
    }

    /* Upload Zone */
    .upload-zone {
        text-align: center;
    }

    /* Preview Container */
    .preview-container {
        position: relative;
        display: inline-block;
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        min-height: 120px;
        min-width: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        transition: border-color 0.3s ease, background-color 0.3s ease;
    }
    .preview-container:hover {
        border-color: var(--theme-primary, #1E3A8A);
        background: #f0f4ff;
    }
    .preview-container.has-new {
        border-color: #10B981;
        border-style: solid;
        background: #f0fdf4;
    }

    .preview-container-sm {
        min-height: 90px;
        min-width: 120px;
        padding: 12px;
    }

    /* Preview Image */
    .preview-image {
        max-height: 100px;
        max-width: 200px;
        object-fit: contain;
        border-radius: 8px;
    }
    .preview-image-sm {
        max-height: 64px;
        max-width: 64px;
    }

    /* Preview Badge */
    .preview-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 0.65rem;
        padding: 3px 8px;
        border-radius: 20px;
    }

    /* No Image Placeholder */
    .no-image-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
    }

    /* Upload button wrapper */
    .upload-input-wrap {
        max-width: 200px;
        margin: 0 auto;
    }

    /* Selected file info */
    .selected-file {
        animation: fadeInUp 0.3s ease;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Save Button */
    #saveBtn {
        border-radius: 10px;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    #saveBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15) !important;
    }
    #saveBtn:active {
        transform: translateY(0);
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ========== IMAGE PREVIEW HANDLER ==========
    function setupImagePreview(inputId, previewId, containerId, placeholderId, fileNameId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const container = document.getElementById(containerId);
        const placeholder = document.getElementById(placeholderId);
        const fileNameEl = document.getElementById(fileNameId);

        if (!input || !preview) return;

        input.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                input.value = '';
                return;
            }

            // Validate file size (logo: 2MB, favicon: 1MB)
            const maxSize = inputId === 'faviconInput' ? 1 * 1024 * 1024 : 2 * 1024 * 1024;
            if (file.size > maxSize) {
                const maxLabel = inputId === 'faviconInput' ? '1MB' : '2MB';
                alert('File size exceeds ' + maxLabel + '. Please select a smaller image.');
                input.value = '';
                return;
            }

            // Read and display preview
            const reader = new FileReader();
            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.classList.remove('d-none');

                // Hide placeholder if it exists
                if (placeholder) {
                    placeholder.classList.add('d-none');
                }

                // Add visual indicator for new image
                container.classList.add('has-new');

                // Update badge
                let badge = container.querySelector('.preview-badge');
                if (badge) {
                    badge.textContent = 'New';
                    badge.classList.remove('bg-success');
                    badge.classList.add('bg-info');
                } else {
                    badge = document.createElement('span');
                    badge.className = 'preview-badge badge bg-info';
                    badge.textContent = 'New';
                    container.appendChild(badge);
                }

                // Show file name
                if (fileNameEl) {
                    fileNameEl.classList.remove('d-none');
                    fileNameEl.querySelector('span').textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                }
            };
            reader.readAsDataURL(file);
        });
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // Initialize previews
    setupImagePreview('logoInput', 'logoPreview', 'logoPreviewContainer', 'logoPlaceholder', 'logoFileName');
    setupImagePreview('faviconInput', 'faviconPreview', 'faviconPreviewContainer', 'faviconPlaceholder', 'faviconFileName');

    // ========== COLOR PICKER LIVE PREVIEW ==========
    const primaryColor = document.getElementById('primaryColor');
    const secondaryColor = document.getElementById('secondaryColor');
    const primaryHex = document.getElementById('primaryHex');
    const secondaryHex = document.getElementById('secondaryHex');
    const previewPrimary = document.getElementById('previewPrimary');
    const previewSecondary = document.getElementById('previewSecondary');
    const previewNavbar = document.getElementById('previewNavbar');

    function updateColorPreview() {
        const pColor = primaryColor.value;
        const sColor = secondaryColor.value;

        primaryHex.textContent = pColor;
        secondaryHex.textContent = sColor;

        previewPrimary.style.background = pColor;
        previewSecondary.style.background = sColor;
        previewNavbar.style.background = 'linear-gradient(90deg, ' + pColor + ' 0%, ' + sColor + ' 100%)';
    }

    if (primaryColor) primaryColor.addEventListener('input', updateColorPreview);
    if (secondaryColor) secondaryColor.addEventListener('input', updateColorPreview);

    // ========== DRAG & DROP SUPPORT ==========
    function setupDragDrop(zoneId, inputId) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        if (!zone || !input) return;

        ['dragenter', 'dragover'].forEach(event => {
            zone.addEventListener(event, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.style.borderColor = 'var(--theme-primary, #1E3A8A)';
                zone.style.background = '#eef2ff';
            });
        });

        ['dragleave', 'drop'].forEach(event => {
            zone.addEventListener(event, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.style.borderColor = '';
                zone.style.background = '';
            });
        });

        zone.addEventListener('drop', function (e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    setupDragDrop('logoPreviewContainer', 'logoInput');
    setupDragDrop('faviconPreviewContainer', 'faviconInput');

    // ========== SAVE BUTTON FEEDBACK ==========
    document.getElementById('settingsForm').addEventListener('submit', function () {
        const btn = document.getElementById('saveBtn');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        btn.disabled = true;
    });
});
</script>
@endsection
