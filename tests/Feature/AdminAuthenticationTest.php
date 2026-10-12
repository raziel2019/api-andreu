<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function protectedPages(): array
    {
        return array_map(fn (string $path): array => [$path], ['/admin', '/admin/home', '/admin/settings', '/admin/about', '/admin/services', '/admin/portfolio', '/admin/contact']);
    }

    #[DataProvider('protectedPages')]
    public function test_guests_are_redirected_to_admin_login(string $path): void
    {
        $this->get($path)->assertRedirect('/admin/login');
    }

    #[DataProvider('protectedPages')]
    public function test_an_administrator_can_view_every_cms_screen(string $path): void
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)->get($path)->assertOk()->assertSee('Identidad y contacto')->assertSee('Contenidos y traducciones');
    }

    public function test_an_administrator_can_log_in_with_a_session(): void
    {
        $user = User::factory()->administrator()->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertSee('Un lugar para cuidar tu web.');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->administrator()->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_non_administrator_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_non_administrator_cannot_access_or_update_cms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->put('/admin/home', [])->assertForbidden();
        $this->put('/admin/settings', [])->assertForbidden();
    }

    public function test_an_administrator_can_log_out_and_cannot_reuse_the_session(): void
    {
        $user = User::factory()->administrator()->create();

        $this->actingAs($user)->post('/admin/logout')->assertRedirect('/admin/login');

        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_attempts_are_throttled(): void
    {
        $email = 'throttled@example.test';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => $email, 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        }

        $this->post('/admin/login', ['email' => $email, 'password' => 'incorrecta'])->assertTooManyRequests();
    }

    public function test_state_changes_require_csrf_tokens(): void
    {
        $this->app['env'] = 'local';

        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'incorrecta'])->assertStatus(419);
    }

    public function test_public_registration_is_absent(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', [])->assertNotFound();
    }
}
