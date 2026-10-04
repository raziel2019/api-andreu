<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhaseTwoSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function endpoints(): array
    {
        return [
            ['GET', '/admin/about'], ['GET', '/admin/services/create'], ['GET', '/admin/services/1/edit'],
            ['GET', '/admin/services/page'], ['GET', '/admin/portfolio'], ['GET', '/admin/contact'], ['GET', '/admin/contact/options/create'], ['GET', '/admin/contact/options/1/edit'],
            ['PUT', '/admin/about'], ['PUT', '/admin/services/page'], ['POST', '/admin/services'], ['PUT', '/admin/services/1'], ['PUT', '/admin/services/order'], ['DELETE', '/admin/services/1'],
            ['PUT', '/admin/portfolio'], ['PUT', '/admin/contact'], ['POST', '/admin/contact/options'], ['PUT', '/admin/contact/options/1'], ['PUT', '/admin/contact/options/order'], ['DELETE', '/admin/contact/options/1'],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_guests_cannot_access_section_forms_or_mutations(string $method, string $path): void
    {
        $this->cmsAdmin();

        $this->call($method, $path, ['confirm_delete' => 1])->assertRedirect('/admin/login');

        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('contact_service_options', 6);
        $this->assertDatabaseCount('about_benefits', 3);
    }

    #[DataProvider('endpoints')]
    public function test_authenticated_non_admin_cannot_access_section_forms_or_mutations(string $method, string $path): void
    {
        $this->cmsAdmin();
        $user = User::factory()->create();

        $this->actingAs($user)->call($method, $path, ['confirm_delete' => 1])->assertForbidden();

        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('contact_service_options', 6);
    }

    public function test_mutations_require_csrf_tokens(): void
    {
        $admin = $this->cmsAdmin();
        $this->app['env'] = 'local';

        $this->actingAs($admin)->put('/admin/about', [])->assertStatus(419);
        $this->post('/admin/services', [])->assertStatus(419);
        $this->delete('/admin/contact/options/1', ['confirm_delete' => 1])->assertStatus(419);

        $this->assertDatabaseCount('services', 4);
        $this->assertDatabaseCount('contact_service_options', 6);
    }
}
