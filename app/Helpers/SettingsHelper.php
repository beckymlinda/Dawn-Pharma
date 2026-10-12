<?php

use App\Models\Setting;

if (!function_exists('business_settings')) {
    /**
     * All business settings: values saved under Settings, falling back to
     * config/business.php. Loaded once per request.
     */
    function business_settings(bool $refresh = false): array
    {
        if ($refresh || !app()->bound('business.settings')) {
            try {
                $saved = Setting::pluck('value', 'key')->filter(fn ($value) => $value !== null && $value !== '')->toArray();
            } catch (\Throwable $e) {
                // Database not reachable yet (e.g. during install or before migrations)
                $saved = [];
            }

            app()->instance('business.settings', array_merge(config('business', []), $saved));
        }

        return app('business.settings');
    }
}

if (!function_exists('setting')) {
    /**
     * One business setting, e.g. setting('system_name').
     */
    function setting(string $key, $default = null)
    {
        return business_settings()[$key] ?? $default;
    }
}

if (!function_exists('brand_initials_logo')) {
    /**
     * A square badge with the business initials in the theme colour, used until
     * a logo is uploaded under Settings (so no client shows another's logo).
     */
    function brand_initials_logo(): string
    {
        // Initials from words that start with a letter ("Area 25 Pharmacy" -> "AP")
        $words = array_values(array_filter(preg_split('/\s+/', trim((string) setting('system_name', 'POS'))), fn ($w) => preg_match('/^\p{L}/u', $w))) ?: ['POS'];
        $initials = strtoupper(mb_substr($words[0], 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : mb_substr($words[0], 1, 1)));
        $color = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) setting('primary_color')) ? setting('primary_color') : '#004d3d';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<rect width="128" height="128" rx="28" fill="' . $color . '"/>'
            . '<text x="64" y="64" dy=".35em" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="56" font-weight="700" fill="#fff">'
            . htmlspecialchars($initials, ENT_XML1) . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

if (!function_exists('brand_image_exists')) {
    /**
     * Whether an uploaded image setting points at a file that is really there
     * (a missing file or storage link would otherwise show a broken image).
     */
    function brand_image_exists(?string $path): bool
    {
        return !empty($path) && is_file(public_path($path));
    }
}

if (!function_exists('brand_logo_url')) {
    function brand_logo_url(): string
    {
        return brand_image_exists(setting('logo')) ? asset(setting('logo')) : brand_initials_logo();
    }
}

if (!function_exists('brand_favicon_url')) {
    function brand_favicon_url(): string
    {
        return brand_image_exists(setting('favicon')) ? asset(setting('favicon')) : brand_logo_url();
    }
}

if (!function_exists('brand_receipt_logo')) {
    /**
     * The uploaded logo, made small for receipt printers: at most 160x60 px,
     * black and white (thermal printers print no colour), returned as a data URI
     * so the receipt prints straight away without downloading anything (offline too).
     * Made once per uploaded file and kept in storage/app/receipt-logos.
     * Null when no logo is uploaded or the image cannot be read.
     */
    function brand_receipt_logo(): ?string
    {
        $logo = setting('logo');
        if (!brand_image_exists($logo)) {
            return null;
        }
        $source = public_path($logo);

        try {
            // SVG is already small text; use it as it is (unless it is large)
            if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'svg') {
                return filesize($source) <= 30720 ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($source)) : null;
            }
            if (!function_exists('imagecreatefromstring')) {
                return null;
            }

            $cache = storage_path('app/receipt-logos/' . md5($source . '|' . filemtime($source)) . '.png');
            if (!is_file($cache)) {
                $image = @imagecreatefromstring(file_get_contents($source));
                if (!$image) {
                    return null;
                }
                [$w, $h] = [imagesx($image), imagesy($image)];
                $scale = min(160 / $w, 60 / $h, 1);
                [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

                // White background (transparent parts must not print black), then shrink and make grey
                $small = imagecreatetruecolor($nw, $nh);
                imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
                imagecopyresampled($small, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagefilter($small, IMG_FILTER_GRAYSCALE);
                imagefilter($small, IMG_FILTER_CONTRAST, -20);
                imagetruecolortopalette($small, true, 16);   // 16 greys: a few KB
                for ($i = 0, $n = imagecolorstotal($small); $i < $n; $i++) {
                    // Reducing colours can leave a slight tint; make each entry a true grey
                    $c = imagecolorsforindex($small, $i);
                    $grey = (int) round(($c['red'] + $c['green'] + $c['blue']) / 3);
                    imagecolorset($small, $i, $grey, $grey, $grey);
                }

                if (!is_dir(dirname($cache))) {
                    mkdir(dirname($cache), 0775, true);
                }
                imagepng($small, $cache, 9);
            }

            return 'data:image/png;base64,' . base64_encode(file_get_contents($cache));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
