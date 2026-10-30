<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** First-run only: creates the first Admin. Further staff are added in the admin by that Admin. */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--name=} {--email=}';

    protected $description = 'Create an Admin account (asks for the password, never stores it in history)';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);

        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email address', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.');
        if (User::where('email', $email)->exists()) {
            $this->error("An account for {$email} already exists.");

            return self::FAILURE;
        }
        $pw = password('Password (at least 12 characters)', required: true, validate: fn ($v) => strlen($v) >= 12 ? null : 'Use at least 12 characters.');

        $user = User::create(['name' => $name, 'email' => $email, 'password' => $pw, 'is_active' => true]);
        $user->assignRole(User::ADMIN);
        activity('users')->performedOn($user)->log('Admin account created from the command line');

        $this->info("Admin created. Sign in at /admin; you will be asked to set up the authenticator app on first sign-in.");

        return self::SUCCESS;
    }
}
