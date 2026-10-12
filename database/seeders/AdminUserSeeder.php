<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $email = config('cms.admin_email');
        $password = config('cms.admin_password');

        if (! $email && ! $password) {
            return;
        }

        Validator::make(compact('email', 'password'), [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:1024'],
        ])->validate();

        $user = User::firstOrNew([
            'email' => mb_strtolower(trim($email)),
        ]);

        if (! $user->exists) {
            $user->fill([
                'name' => 'Administrador',
                'password' => $password,
            ]);
        }

        $user->forceFill([
            'is_admin' => true,
        ])->save();
    }
}
