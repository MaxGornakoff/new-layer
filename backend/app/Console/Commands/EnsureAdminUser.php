<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class EnsureAdminUser extends Command
{
    protected $signature = 'user:ensure-admin
                            {email=admin@new-layer.ru : Email администратора}
                            {--name=Администратор : Имя}
                            {--password= : Пароль (если не указан — спросит интерактивно)}';

    protected $description = 'Создать администратора или назначить роль admin существующему пользователю';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->option('name');
        $password = $this->option('password');

        if (! is_string($password) || $password === '') {
            $password = $this->secret('Пароль администратора');
        }

        $validator = Validator::make(
            [
                'email' => $email,
                'password' => $password,
                'name' => $name,
            ],
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
                'name' => ['required', 'string', 'max:255'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
            ]);
            $this->info("Пользователь {$email} обновлён и назначен администратором.");
        } else {
            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
            ]);
            $this->info("Администратор {$email} создан.");
        }

        return self::SUCCESS;
    }
}
