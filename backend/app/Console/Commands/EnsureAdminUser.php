<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnsureAdminUser extends Command
{
    protected $signature = 'user:ensure-admin {email=admin@shop.local}';

    protected $description = 'Ensure the given user has the admin role';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found: '.$this->argument('email'));

            return self::FAILURE;
        }

        $user->update(['role' => User::ROLE_ADMIN]);

        $this->info("User {$user->email} is now admin.");

        return self::SUCCESS;
    }
}
