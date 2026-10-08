<?php

namespace App\Console\Commands;

use App\Http\Controllers\SetupController;
use App\Models\User;
use Illuminate\Console\Command;

/** Adds a login for the admin portal (the software owner's staff, not a shop user) */
class CreatePlatformAdmin extends Command
{
    protected $signature = 'platform:create-admin {email} {--name=}';

    protected $description = 'Create an admin portal login (platform admin)';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        if (User::withoutGlobalScopes()->whereNull('tenant_id')->where('email', $email)->exists()) {
            $this->error("A platform admin with {$email} already exists.");
            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $password = $this->secret('Password (10+ characters)');
        if (strlen((string) $password) < 10 || $password !== $this->secret('Repeat password')) {
            $this->error('Passwords must match and be at least 10 characters.');
            return self::FAILURE;
        }

        SetupController::makePlatformAdmin($name, $email, $password);
        $this->info("Platform admin {$email} created. Log in at " . url('/platform/login'));

        return self::SUCCESS;
    }
}
