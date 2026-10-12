<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProvisionAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_production_database_seeding_does_not_create_an_admin_even_with_credentials(): void
    {
        Storage::fake('public');
        config(['cms.admin_email' => 'environment@example.test', 'cms.admin_password' => 'environment-password-123']);
        User::query()->count();
        $this->app['env'] = 'production';

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_explicit_production_provisioning_hashes_password_and_is_idempotent(): void
    {
        User::query()->count();
        $this->app['env'] = 'production';

        $this->artisan('cms:provision-admin', ['email' => 'NewAdmin@example.test'])
            ->expectsQuestion('Contrasenya (mínim 12 caràcters)', 'secure-password-123')
            ->expectsQuestion('Confirma la contrasenya', 'secure-password-123')
            ->expectsOutput('Administrador creat.')->assertExitCode(0);

        $user = User::where('email', 'newadmin@example.test')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
        $hash = $user->password;
        $this->artisan('cms:provision-admin', ['email' => 'newadmin@example.test', '--no-interaction' => true])
            ->expectsOutput('L’administrador ja existeix. No s’ha modificat la contrasenya.')->assertExitCode(0);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_existing_non_admin_is_not_promoted_or_given_a_new_password(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.test']);
        $before = $user->fresh()->getAttributes();

        $this->artisan('cms:provision-admin', ['email' => $user->email])->assertExitCode(1);

        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    public function test_non_interactive_command_cannot_create_an_admin_from_environment_secrets(): void
    {
        config(['cms.admin_email' => 'environment@example.test', 'cms.admin_password' => 'environment-password-123']);
        User::query()->count();

        $this->artisan('cms:provision-admin', ['email' => 'new@example.test', '--no-interaction' => true])
            ->expectsOutput('Cal una consola interactiva per introduir i confirmar la contrasenya.')->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public static function invalidPasswords(): array
    {
        return [
            'short' => ['short', 'short'],
            'mismatch' => ['secure-password-123', 'another-password-123'],
            'empty' => ['', ''],
            'too many bytes' => [str_repeat('a', 73), str_repeat('a', 73)],
            'multibyte limit' => [str_repeat('é', 37), str_repeat('é', 37)],
            'null byte' => ["secure\0password", "secure\0password"],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_or_unconfirmed_password_does_not_create_an_admin(string $password, string $confirmation): void
    {
        User::query()->count();

        $this->artisan('cms:provision-admin', ['email' => 'new@example.test'])
            ->expectsQuestion('Contrasenya (mínim 12 caràcters)', $password)
            ->expectsQuestion('Confirma la contrasenya', $confirmation)->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_email_is_rejected_before_requesting_password(): void
    {
        User::query()->count();

        $this->artisan('cms:provision-admin', ['email' => 'not-an-email'])->expectsOutput('Indica un correu electrònic vàlid.')->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}
