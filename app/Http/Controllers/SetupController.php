<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Server setup for hosting without a terminal (cPanel): run new migrations,
 * create the first platform admin, clear caches, re-link storage.
 *
 * Only works when PLATFORM_SETUP_TOKEN is set in .env (16+ characters) and the
 * same token is given, e.g. https://admin.example.com/setup?token=...
 * Remove the token from .env when you are done.
 */
class SetupController extends Controller
{
    public function show(Request $request)
    {
        $this->authorizeToken($request);

        Artisan::call('migrate:status');
        $status = Artisan::output();

        return view('setup.index', [
            'token' => $request->input('token'),
            'pending' => substr_count($status, 'Pending'),
            'migrated' => Schema::hasTable('tenants') && Schema::hasColumn('users', 'is_platform_admin'),
            'hasPlatformAdmin' => Schema::hasColumn('users', 'is_platform_admin')
                && User::withoutGlobalScopes()->where('is_platform_admin', true)->exists(),
            'output' => session('output'),
        ]);
    }

    public function run(Request $request, string $action)
    {
        $this->authorizeToken($request);

        switch ($action) {
            case 'migrate':
                // Only migrations that have not run yet; existing data is kept
                Artisan::call('migrate', ['--force' => true]);
                break;
            case 'clear-cache':
                Artisan::call('optimize:clear');
                break;
            case 'storage-link':
                if (is_link(public_path('storage'))) {
                    File::delete(public_path('storage'));
                }
                Artisan::call('storage:link');
                break;
            default:
                abort(404);
        }

        return redirect('/setup?token=' . urlencode($request->input('token')))->with('output', Artisan::output());
    }

    public function createAdmin(Request $request)
    {
        $this->authorizeToken($request);
        abort_unless(Schema::hasColumn('users', 'is_platform_admin'), 409, 'Run the migrations first.');
        abort_if(User::withoutGlobalScopes()->where('is_platform_admin', true)->exists(), 409,
            'A platform admin already exists. Add more with: php artisan platform:create-admin');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:10|confirmed',
        ]);

        $this->makePlatformAdmin($data['name'], $data['email'], $data['password']);

        return redirect('/setup?token=' . urlencode($request->input('token')))
            ->with('output', "Platform admin {$data['email']} created. Log in at the admin portal.");
    }

    public static function makePlatformAdmin(string $name, string $email, string $password): User
    {
        // Created outside any shop, so it is not stamped with one
        return with_tenant(null, function () use ($name, $email, $password) {
            $user = new User(['name' => $name, 'email' => strtolower($email), 'password' => $password]);
            $user->is_platform_admin = true;
            $user->save();

            return $user;
        });
    }

    private function authorizeToken(Request $request): void
    {
        $expected = (string) config('app.setup_token');
        $given = (string) $request->input('token');

        // Not configured, or wrong token: behave as if the page does not exist
        if (strlen($expected) < 16 || !hash_equals($expected, $given)) {
            abort(404);
        }
    }
}
