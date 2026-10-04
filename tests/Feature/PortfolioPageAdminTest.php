<?php

namespace Tests\Feature;

use App\Models\PortfolioPage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioPageAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_edit_construction_copy_and_state_without_project_crud(): void
    {
        $admin = $this->cmsAdmin();
        $page = PortfolioPage::findOrFail(1);
        $payload = [...$this->contentPayload($page), 'message' => $this->bilingual('Tornarem aviat', 'Volveremos pronto'), 'under_construction' => 0,
            'cta_label' => ['ca' => null, 'es' => null], 'linkedin_destination' => 'https://www.linkedin.com/in/example'];

        $this->actingAs($admin)->put('/admin/portfolio', $payload)->assertRedirect('/admin/portfolio')->assertSessionHas('status');

        $this->assertFalse($page->fresh()->under_construction);
        $this->assertSame(['ca' => 'Tornarem aviat', 'es' => 'Volveremos pronto'], $page->fresh()->message);
        $this->getJson('/api/v1/pages/portfolio?locale=es')->assertJsonPath('data.message', 'Volveremos pronto')->assertJsonPath('data.under_construction', false)->assertJsonPath('data.cta_label', null);
        $this->get('/admin/portfolio')->assertSee('La gestió de projectes encara no forma part del CMS.');
        $this->get('/admin/projects')->assertNotFound();
        $this->getJson('/api/v1/projects')->assertNotFound();
    }

    public static function invalidDestinations(): array
    {
        return [['linkedin_destination', 'javascript:alert(1)'], ['linkedin_destination', 'ftp://example.test'], ['cta_destination', '//example.test'], ['cta_destination', 'javascript:alert(1)']];
    }

    #[DataProvider('invalidDestinations')]
    public function test_invalid_portfolio_urls_are_rejected(string $field, string $value): void
    {
        $admin = $this->cmsAdmin();
        $page = PortfolioPage::findOrFail(1);
        $payload = [...$this->contentPayload($page), $field => $value];

        $this->actingAs($admin)->put('/admin/portfolio', $payload)->assertSessionHasErrors($field);

        $this->assertTrue($page->fresh()->under_construction);
        $this->assertNull($page->fresh()->linkedin_destination);
    }
}
