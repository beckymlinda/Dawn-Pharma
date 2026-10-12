<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class ReceiptLogoTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private array $made = [];

    protected function tearDown(): void
    {
        foreach ($this->made as $file) {
            File::delete($file);
        }
        parent::tearDown();
    }

    /** A big, colourful, partly transparent PNG like a real logo export */
    private function bigLogo(): UploadedFile
    {
        $img = imagecreatetruecolor(1600, 800);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        for ($i = 0; $i < 400; $i++) {
            imagefilledellipse($img, random_int(0, 1600), random_int(0, 800), 120, 120, imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        }
        $path = tempnam(sys_get_temp_dir(), 'logo') . '.png';
        imagepng($img, $path, 9);

        return new UploadedFile($path, 'logo.png', 'image/png', null, true);
    }

    public function test_uploaded_logo_is_printed_small_and_black_and_white(): void
    {
        $admin = $this->userWithPermissions(['add_pos_sell'], 'admin');
        $upload = $this->bigLogo();
        $this->assertGreaterThan(50000, $upload->getSize());   // a large logo file

        // Saved as Settings > Branding saves it (straight into public/, so this test does not depend on the storage link)
        $relative = 'receipt-logo-test-' . uniqid() . '.png';
        copy($upload->getRealPath(), public_path($relative));
        $this->made[] = public_path($relative);
        \App\Models\Setting::updateOrCreate(['key' => 'logo'], ['value' => $relative]);
        business_settings(true);

        $uri = brand_receipt_logo();
        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $png = base64_decode(substr($uri, strlen('data:image/png;base64,')));
        $this->assertLessThan(15 * 1024, strlen($png), 'receipt logo should be a few KB');

        $img = imagecreatefromstring($png);
        $this->assertLessThanOrEqual(160, imagesx($img));
        $this->assertLessThanOrEqual(60, imagesy($img));
        $this->assertEqualsWithDelta(120, imagesx($img), 1);   // 2:1 kept
        // Every pixel grey (no colour), transparent corner printed white
        for ($x = 0; $x < imagesx($img); $x += 7) {
            for ($y = 0; $y < imagesy($img); $y += 7) {
                $c = imagecolorsforindex($img, imagecolorat($img, $x, $y));
                $this->assertTrue($c['red'] === $c['green'] && $c['green'] === $c['blue'], "pixel $x,$y has colour");
            }
        }

        // Made once: the same file is used next time
        $this->assertSame($uri, brand_receipt_logo());

        // The till gets it with the other receipt details
        $this->openRegisterFor($admin);
        $this->actingAs($admin)->get('/pos/new')->assertOk()->assertSee('"receipt_logo":"data:image\/png;base64,', false);
    }

    public function test_no_uploaded_logo_means_no_logo_on_the_receipt(): void
    {
        $this->assertNull(brand_receipt_logo());

        $admin = $this->userWithPermissions(['add_pos_sell'], 'admin');
        $this->openRegisterFor($admin);
        $this->actingAs($admin)->get('/pos/new')->assertOk()->assertSee('"receipt_logo":null', false);
    }
}
