<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\PortfolioCategory;
use App\Models\PortfolioPage;
use App\Models\PortfolioProject;
use App\Models\User;
use App\Services\MediaCatalog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioProjectTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [...['title' => $this->bilingual(), 'description' => $this->bilingual(), 'body' => $this->bilingual('<h2>Context</h2><p>Memòria</p>', '<h2>Contexto</h2><p>Memoria</p>'), 'slug' => 'projecte-real', 'status' => 'draft', 'sort_order' => 2, 'gallery' => []], ...$overrides];
    }

    public function test_admin_creates_edits_and_deletes_projects_while_retaining_media(): void
    {
        $admin = $this->cmsAdmin();
        $category = PortfolioCategory::factory()->create();
        $file = UploadedFile::fake()->image('archive.jpg');
        Storage::disk('public')->put('cms/shared.jpg', file_get_contents($file->getRealPath()));
        $asset = app(MediaCatalog::class)->register('cms/shared.jpg');
        $payload = $this->payload(['portfolio_category_id' => $category->id, 'featured_image_asset_id' => $asset->id, 'gallery' => [['image_asset_id' => $asset->id, 'alt' => $this->bilingual(), 'sort_order' => 1]]]);

        $this->actingAs($admin)->post('/admin/portfolio/projects', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $project = PortfolioProject::firstOrFail();
        $this->assertSame('cms/shared.jpg', $project->featured_image_path);
        $this->assertSame('cms/shared.jpg', $project->gallery[0]['path']);
        $this->assertSame($category->id, $project->portfolio_category_id);
        $this->get('/admin/portfolio/projects/'.$project->id.'/edit')->assertSee('Cos del projecte')->assertSee('shared.jpg', false);

        $this->put('/admin/portfolio/projects/'.$project->id, $this->payload(['status' => 'published', 'year' => 2025, 'client' => 'Client real']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('published', $project->fresh()->status);
        $this->assertSame(2025, $project->fresh()->year);
        $this->assertSame('cms/shared.jpg', $project->fresh()->featured_image_path);
        $this->assertSame([], $project->fresh()->gallery);
        $this->delete('/admin/portfolio/projects/'.$project->id, ['confirm_delete' => 1])->assertRedirect();
        $this->assertModelMissing($project);
        $this->assertModelExists($asset);
        Storage::disk('public')->assertExists('cms/shared.jpg');
    }

    public function test_category_crud_preserves_projects_when_category_is_removed(): void
    {
        $admin = $this->cmsAdmin();
        $this->actingAs($admin)->post('/admin/portfolio/categories', ['title' => $this->bilingual(), 'slug' => 'arxius', 'sort_order' => 0])->assertSessionHasNoErrors()->assertRedirect();
        $category = PortfolioCategory::firstOrFail();
        $project = PortfolioProject::factory()->create(['portfolio_category_id' => $category->id]);
        $this->put('/admin/portfolio/categories/'.$category->id, ['title' => $this->bilingual('Arxius', 'Archivos'), 'slug' => 'arxius', 'sort_order' => 5])->assertSessionHasNoErrors();
        $this->assertSame('Arxius', $category->fresh()->translated('title', 'ca'));
        $this->delete('/admin/portfolio/categories/'.$category->id, ['confirm_delete' => 1])->assertRedirect();
        $this->assertNull($project->fresh()->portfolio_category_id);
    }

    public function test_listing_filters_orders_and_localizes_only_published_projects(): void
    {
        PortfolioPage::factory()->create(['id' => 1, 'active' => true, 'under_construction' => false]);
        $category = PortfolioCategory::factory()->create(['slug' => 'arxius', 'title' => $this->bilingual('Arxius', 'Archivos')]);
        $project = PortfolioProject::factory()->create(['portfolio_category_id' => $category->id, 'status' => 'published', 'title' => $this->bilingual('Arxiu familiar', 'Archivo familiar')]);
        PortfolioProject::factory()->create(['status' => 'published']);
        $draft = PortfolioProject::factory()->create(['portfolio_category_id' => $category->id]);

        $this->getJson('/api/v1/projects?category=arxius&locale=es')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $project->id)->assertJsonPath('data.0.title', 'Archivo familiar')->assertJsonPath('data.0.category.title', 'Archivos')->assertJsonPath('meta.locale', 'es')->assertJsonMissingPath('data.0.body');
        $this->getJson('/api/v1/projects?category=unknown')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/projects/'.$draft->slug)->assertNotFound();
        $this->getJson('/api/v1/projects/'.$project->slug)->assertJsonPath('data.title', 'Arxiu familiar')->assertJsonPath('data.body', '<p>Cos de prova</p>')->assertJsonPath('data.gallery', []);
        $this->getJson('/api/v1/projects?category[]=bad')->assertUnprocessable();
    }

    public function test_related_projects_prioritize_category_exclude_current_and_drafts_and_limit_three(): void
    {
        PortfolioPage::factory()->create(['id' => 1, 'active' => true, 'under_construction' => false]);
        $category = PortfolioCategory::factory()->create();
        $project = PortfolioProject::factory()->create(['status' => 'published', 'portfolio_category_id' => $category->id]);
        PortfolioProject::factory()->count(4)->create(['status' => 'published', 'sort_order' => 0]);
        $related = PortfolioProject::factory()->create(['status' => 'published', 'portfolio_category_id' => $category->id, 'sort_order' => 9]);
        $draft = PortfolioProject::factory()->create(['portfolio_category_id' => $category->id]);

        $this->getJson('/api/v1/projects/'.$project->slug)->assertJsonCount(3, 'meta.related_projects')->assertJsonPath('meta.related_projects.0.id', $related->id)->assertJsonMissing(['id' => $draft->id]);
    }

    public function test_page_visibility_and_construction_block_all_project_endpoints(): void
    {
        $page = PortfolioPage::factory()->create(['id' => 1, 'active' => false, 'under_construction' => false]);
        $project = PortfolioProject::factory()->create(['status' => 'published']);
        foreach ([['active' => false], ['active' => true, 'under_construction' => true]] as $attributes) {
            $page->update($attributes);
            $this->getJson('/api/v1/projects')->assertNotFound();
            $this->getJson('/api/v1/projects/'.$project->slug)->assertNotFound();
        }
    }

    public function test_rich_text_keeps_structure_and_safe_links_and_removes_active_content(): void
    {
        $admin = $this->cmsAdmin();
        $html = '<h2 onclick="evil()">Context</h2><p><strong>Text</strong><a href="https://example.com" style="color:red">Safe</a><a href="javascript:evil()">Bad</a><a href="&#106;avascript:evil()">Encoded</a><a href="//evil.test">Network</a></p><ul><li>Item</li></ul><script>evil()</script><svg onload="evil()"><a>SVG</a></svg><iframe src="evil"></iframe><img src="x" onerror="evil()">';
        $this->actingAs($admin)->post('/admin/portfolio/projects', $this->payload(['body' => ['ca' => $html, 'es' => $html]]))->assertSessionHasNoErrors();
        $project = PortfolioProject::firstOrFail();
        $this->assertSame('<h2>Context</h2><p><strong>Text</strong><a href="https://example.com">Safe</a><a>Bad</a><a>Encoded</a><a>Network</a></p><ul><li>Item</li></ul>', $project->body['ca']);
    }

    public function test_gallery_uploads_are_sorted_localized_and_reusable(): void
    {
        $admin = $this->cmsAdmin();
        PortfolioPage::findOrFail(1)->update(['under_construction' => false]);
        $payload = $this->payload(['status' => 'published', 'gallery' => [
            ['image' => UploadedFile::fake()->image('later.jpg'), 'alt' => $this->bilingual('Segona', 'Segunda'), 'sort_order' => 5],
            ['image' => UploadedFile::fake()->image('first.jpg'), 'alt' => $this->bilingual('Primera', 'Primera'), 'sort_order' => 0],
        ]]);
        $this->actingAs($admin)->post('/admin/portfolio/projects', $payload)->assertSessionHasNoErrors();
        $this->getJson('/api/v1/projects/projecte-real?locale=es')->assertJsonPath('data.gallery.0.alt', 'Primera')->assertJsonPath('data.gallery.1.alt', 'Segunda');
        $this->assertSame(2, MediaAsset::where('path', 'like', 'cms/%')->count());
    }

    public function test_invalid_media_and_duplicate_slug_are_rejected_without_changes(): void
    {
        $admin = $this->cmsAdmin();
        $project = PortfolioProject::factory()->create(['slug' => 'projecte-real']);
        $this->actingAs($admin)->post('/admin/portfolio/projects', $this->payload(['featured_image_asset_id' => 999999]))->assertSessionHasErrors(['slug', 'featured_image_asset_id']);
        $this->assertSame(1, PortfolioProject::count());
        $this->delete('/admin/portfolio/projects/'.$project->id)->assertSessionHasErrors('confirm_delete');
        $this->assertModelExists($project);
    }

    public function test_guest_and_non_admin_cannot_manage_portfolio(): void
    {
        $this->get('/admin/portfolio/projects')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->post('/admin/portfolio/projects', $this->payload())->assertForbidden();
        $this->get('/admin/portfolio/categories')->assertForbidden();
        $this->assertDatabaseCount('portfolio_projects', 0);
    }
}
