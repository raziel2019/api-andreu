<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;

class ProvisionAdmin extends Command
{
    protected $signature = 'cms:provision-admin {email : Correu del nou administrador}';

    protected $description = 'Crea explícitament un administrador amb contrasenya oculta, sense modificar comptes existents';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']]);
        if ($validator->fails()) {
            $this->error('Indica un correu electrònic vàlid.');

            return self::FAILURE;
        }
        $existing = User::where('email', $email)->first();
        if ($existing) {
            if (! $existing->is_admin) {
                $this->error('Aquest compte ja existeix i no és administrador. No se n’han modificat els permisos.');

                return self::FAILURE;
            }
            $this->info('L’administrador ja existeix. No s’ha modificat la contrasenya.');

            return self::SUCCESS;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Cal una consola interactiva per introduir i confirmar la contrasenya.');

            return self::FAILURE;
        }
        $password = $this->secret('Contrasenya (mínim 12 caràcters)', false);
        $confirmation = $this->secret('Confirma la contrasenya', false);
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ]);
        if ($validator->fails() || ! is_string($password) || strlen($password) > 72 || str_contains($password, "\0")) {
            $this->error('La contrasenya ha de tenir com a mínim 12 caràcters, un màxim de 72 bytes, cap caràcter nul i coincidir amb la confirmació.');

            return self::FAILURE;
        }
        try {
            $user = new User(['name' => 'Administrador', 'email' => $email, 'password' => $password]);
            $user->forceFill(['is_admin' => true])->save();
        } catch (UniqueConstraintViolationException) {
            $this->error('El compte s’ha creat simultàniament. No s’ha modificat. Torna a executar el comandament.');

            return self::FAILURE;
        }
        $this->info('Administrador creat.');

        return self::SUCCESS;
    }
}
