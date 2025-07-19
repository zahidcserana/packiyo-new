<?php

namespace App\Console\Commands;

use App\Http\Requests\User\StoreRequest;
use App\Models\Country;
use App\Models\UserRole;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create {name} {email} {password} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $role = $this->argument('role');

        $userRole = match ($role) {
            'member' => UserRole::ROLE_MEMBER,
            'admin' => UserRole::ROLE_ADMINISTRATOR,
            default => UserRole::ROLE_DEFAULT
        };

        app('user')->store(StoreRequest::make([
            'contact_information' => [
                'name' => $name
            ],
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'user_role_id' => $userRole
        ]));
    }
}
