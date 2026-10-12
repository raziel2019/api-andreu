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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioCmsCompletionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [...['title' => $this->bilingual(), 'slug' => 'arxiu-real', 'status' => 'draft', 'sort_order' => 0], ...$overrides];
    }

    public function test_gallery_caption_and_alt_translations_survive_reuse_and_reordering(): void
    {
        $admin = $this->cmsAdmin();
        PortfolioPage::findOrFail(1)->update(['under_construction' => false]);
        $project = PortfolioProject::factory()->create(['slug' => 'arxiu-real']);
        $file = UploadedFile::fake()->image('shared.jpg');
        Storage::disk('public')->put('cms/shared.jpg', file_get_contents($file->getRealPath()));
        $asset = app(MediaCatalog::class)->register('cms/shared.jpg');
        $gallery = [
            ['image_asset_id' => $asset->id, 'alt' => $this->bilingual('Document segon', 'Documento segundo'), 'caption' => $this->bilingual('Fons documental', 'Fondo documental'), 'sort_order' => 7],
            ['image_asset_id' => $asset->id, 'alt' => $this->bilingual('Document primer', 'Documento primero'), 'caption' => $this->bilingual('Arxiu familiar', 'Archivo familiar'), 'sort_order' => 1],
        ];

        $this->actingAs($admin)->put('/admin/portfolio/projects/'.$project->id, $this->payload(['status' => 'published', 'gallery' => $gallery, 'featured_image_asset_id' => $asset->id]))->assertSessionHasNoErrors();

        $this->assertSame('cms/shared.jpg', $project->fresh()->gallery[0]['path']);
        $this->assertSame('Arxiu familiar', $project->fresh()->gallery[0]['caption']['ca']);
        $this->getJson('/api/v1/projects/arxiu-real?locale=es')->assertJsonPath('data.gallery.0.caption', 'Archivo familiar')->assertJsonPath('data.gallery.0.alt', 'Documento primero')->assertJsonPath('data.gallery.1.sort_order', 7);
        $this->getJson('/api/v1/projects/arxiu-real?locale=ca')->assertJsonPath('data.gallery.1.caption', 'Fons documental')->assertJsonPath('data.gallery.1.alt', 'Document segon');
        $this->get('/admin/portfolio/projects/'.$project->id.'/edit')->assertSee('Peu de foto')->assertSee('Archivo familiar');
        $this->assertSame(1, MediaAsset::where('path', 'cms/shared.jpg')->count());
        Storage::disk('public')->assertExists('cms/shared.jpg');
    }

    public function test_legacy_gallery_is_sorted_without_rewriting_it_and_caption_falls_back_to_ca(): void
    {
        PortfolioPage::factory()->create(['id' => 1, 'active' => true, 'under_construction' => false]);
        $gallery = [
            ['path' => 'cms/later.jpg', 'alt' => ['ca' => 'Text antic'], 'sort_order' => 9],
            ['path' => 'cms/first.jpg', 'alt' => ['ca' => 'Primer'], 'caption' => ['ca' => 'Peu català', 'es' => null], 'sort_order' => 0],
        ];
        $project = PortfolioProject::factory()->create(['status' => 'published', 'gallery' => $gallery]);

        $this->getJson('/api/v1/projects/'.$project->slug.'?locale=es')->assertJsonPath('data.gallery.0.caption', 'Peu català')->assertJsonPath('data.gallery.0.alt', 'Primer')->assertJsonPath('data.gallery.1.caption', null)->assertJsonPath('data.gallery.1.alt', 'Text antic');

        $this->assertSame($gallery, $project->fresh()->gallery);
    }

    public function test_unsupported_caption_locale_and_oversized_alt_are_rejected(): void
    {
        $admin = $this->cmsAdmin();

        $this->actingAs($admin)->post('/admin/portfolio/projects', $this->payload(['gallery' => [[
            'image' => UploadedFile::fake()->image('photo.jpg'), 'caption' => ['en' => 'Invalid'], 'alt' => ['ca' => str_repeat('a', 2001)], 'sort_order' => 0,
        ]]]))->assertSessionHasErrors(['gallery.0.caption', 'gallery.0.alt.ca']);

        $this->assertDatabaseCount('portfolio_projects', 0);
        $this->assertSame([], Storage::disk('public')->files('cms'));
    }

    public function test_bulk_order_controls_public_sort_and_rejects_invalid_ids_atomically(): void
    {
        $admin = $this->cmsAdmin();
        PortfolioPage::findOrFail(1)->update(['under_construction' => false]);
        $first = PortfolioProject::factory()->create(['status' => 'published', 'sort_order' => 0]);
        $second = PortfolioProject::factory()->create(['status' => 'published', 'sort_order' => 9]);
        $this->actingAs($admin)->get('/admin/portfolio/projects')->assertSee('Desa l’ordre');

        $this->put('/admin/portfolio/projects/order', ['items' => [['id' => $first->id, 'sort_order' => 5], ['id' => $second->id, 'sort_order' => 0]]])->assertSessionHasNoErrors()->assertRedirect('/admin/portfolio/projects');

        $this->getJson('/api/v1/projects')->assertJsonPath('data.0.id', $second->id)->assertJsonPath('data.1.id', $first->id);
        $this->assertSame(5, $first->fresh()->sort_order);
        $this->put('/admin/portfolio/projects/order', ['items' => [['id' => $first->id, 'sort_order' => 10], ['id' => 999999, 'sort_order' => 0]]])->assertSessionHasErrors('items.1.id');
        $this->assertSame(5, $first->fresh()->sort_order);
        $this->put('/admin/portfolio/projects/order', ['items' => [['id' => $first->id, 'sort_order' => 10], ['id' => $first->id, 'sort_order' => 0]]])->assertSessionHasErrors('items.0.id');
    }

    public function test_publish_and_unpublish_preserves_slug_and_hides_draft(): void
    {
        $admin = $this->cmsAdmin();
        PortfolioPage::findOrFail(1)->update(['under_construction' => false]);
        $project = PortfolioProject::factory()->create(['slug' => 'arxiu-real']);

        $this->actingAs($admin)->put('/admin/portfolio/projects/'.$project->id, $this->payload(['status' => 'published']))->assertSessionHasNoErrors();

        $this->getJson('/api/v1/projects/arxiu-real')->assertOk();
        $this->put('/admin/portfolio/projects/'.$project->id, $this->payload())->assertSessionHasNoErrors();
        $this->getJson('/api/v1/projects/arxiu-real')->assertNotFound();
        $this->getJson('/api/v1/projects')->assertJsonCount(0, 'data');
        $this->assertSame('arxiu-real', $project->fresh()->slug);
    }

    public function test_preview_renders_saved_draft_in_both_locales_without_public_exposure(): void
    {
        $admin = $this->cmsAdmin();
        $project = PortfolioProject::factory()->create(['description' => $this->bilingual('<p><strong>Descripció</strong></p>', '<p><em>Descripción</em></p>'), 'gallery' => [['path' => 'cms/photo.jpg', 'alt' => $this->bilingual(), 'caption' => ['ca' => '<script>caption()</script>'], 'sort_order' => 0]]]);
        DB::table('portfolio_projects')->where('id', $project->id)->update(['body' => json_encode(['ca' => '<h2>Context</h2><script>evil()</script>', 'es' => '<blockquote>Memoria</blockquote>'])]);

        $this->actingAs($admin)->get('/admin/portfolio/projects/'.$project->id.'/preview?locale=ca')->assertOk()->assertHeader('Content-Language', 'ca')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Cache-Control', 'no-store, private')->assertSee('<h2>Context</h2>', false)->assertDontSee('<script>evil()', false)->assertSee('&lt;script&gt;caption()', false);
        $this->get('/admin/portfolio/projects/'.$project->id.'/preview?locale=es')->assertSee('<blockquote>Memoria</blockquote>', false)->assertSee('<em>Descripción</em>', false);
        $this->getJson('/api/v1/projects/'.$project->slug)->assertNotFound();
        $this->get('/admin/portfolio/projects/'.$project->id.'/preview?locale=en')->assertUnprocessable();
        $this->assertSame('draft', $project->fresh()->status);
    }

    public function test_preview_requires_admin_even_when_project_is_published(): void
    {
        $project = PortfolioProject::factory()->create(['status' => 'published']);

        $this->get('/admin/portfolio/projects/'.$project->id.'/preview')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin/portfolio/projects/'.$project->id.'/preview')->assertForbidden();
    }

    public function test_list_and_detail_preserve_exact_contract_and_categories_outside_filter(): void
    {
        PortfolioPage::factory()->create(['id' => 1, 'active' => true, 'under_construction' => false]);
        $category = PortfolioCategory::factory()->create(['slug' => 'arxius']);
        $otherCategory = PortfolioCategory::factory()->create(['slug' => 'recerca']);
        $project = PortfolioProject::factory()->create(['status' => 'published', 'portfolio_category_id' => $category->id]);
        $other = PortfolioProject::factory()->create(['status' => 'published', 'portfolio_category_id' => $otherCategory->id]);
        PortfolioProject::factory()->create(['portfolio_category_id' => PortfolioCategory::factory()->create()->id]);
        $keys = ['id', 'slug', 'title', 'description', 'description_html', 'category', 'featured_image_url', 'featured_image_alt', 'year', 'client', 'sort_order'];

        $list = $this->getJson('/api/v1/projects?category=arxius')->assertOk()->assertJsonCount(1, 'data')->assertJsonCount(2, 'meta.categories')->assertJsonPath('meta.total', 1);
        $detail = $this->getJson('/api/v1/projects/'.$project->slug)->assertOk()->assertJsonPath('meta.related_projects.0.id', $other->id);

        $this->assertSame($keys, array_keys($list->json('data.0')));
        $this->assertSame([...$keys, 'body', 'gallery'], array_keys($detail->json('data')));
        $this->assertSame(['locale', 'page', 'categories', 'current_page', 'last_page', 'total'], array_keys($list->json('meta')));
        $this->assertSame(['locale', 'page', 'related_projects'], array_keys($detail->json('meta')));
    }

    public function test_category_query_count_does_not_grow_with_project_count(): void
    {
        PortfolioPage::factory()->create(['id' => 1, 'active' => true, 'under_construction' => false]);
        PortfolioProject::factory()->create(['status' => 'published', 'portfolio_category_id' => PortfolioCategory::factory()->create()->id]);
        DB::enableQueryLog();
        $this->getJson('/api/v1/projects')->assertOk();
        $initialCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        for ($index = 0; $index < 10; $index++) {
            PortfolioProject::factory()->create(['status' => 'published', 'portfolio_category_id' => PortfolioCategory::factory()->create()->id]);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/projects')->assertOk()->assertJsonCount(11, 'data');

        $this->assertSame($initialCount, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }

    public function test_project_descriptions_keep_all_supported_formatting_in_ca_and_es(): void
    {
        $admin = $this->cmsAdmin();
        PortfolioPage::findOrFail(1)->update(['under_construction' => false]);
        $ca = '<h2>Arxiu</h2><p><strong>Negreta</strong><em>Cursiva</em><a href="https://example.test">Enllaç</a><a href="https://user:secret@example.test">Credencials</a></p><ol><li>Primer</li></ol><ul><li>Document</li></ul><blockquote>Memòria</blockquote>';
        $es = '<h3>Archivo</h3><p><strong>Negrita</strong><em>Cursiva</em></p><blockquote>Memoria</blockquote>';

        $this->actingAs($admin)->post('/admin/portfolio/projects', $this->payload(['status' => 'published', 'description' => ['ca' => $ca, 'es' => $es]]))->assertSessionHasNoErrors();

        $this->getJson('/api/v1/projects/arxiu-real?locale=ca')->assertJsonPath('data.description_html', str_replace(' href="https://user:secret@example.test"', '', $ca));
        $this->getJson('/api/v1/projects/arxiu-real?locale=es')->assertJsonPath('data.description_html', $es);
        $this->get('/admin/portfolio/projects/'.PortfolioProject::firstOrFail()->id.'/edit')->assertSee('data-command="bold"', false)->assertSee('data-source-toggle', false)->assertSee('description[ca]', false)->assertSee('description[es]', false);
    }

    public static function mutations(): array
    {
        return [
            ['POST', '/admin/portfolio/projects'], ['PUT', '/admin/portfolio/projects/1'], ['DELETE', '/admin/portfolio/projects/1'],
            ['PUT', '/admin/portfolio/projects/order'], ['POST', '/admin/portfolio/categories'], ['PUT', '/admin/portfolio/categories/1'], ['DELETE', '/admin/portfolio/categories/1'], ['PUT', '/admin/settings'],
        ];
    }

    #[DataProvider('mutations')]
    public function test_portfolio_and_settings_mutations_require_csrf(string $method, string $path): void
    {
        $admin = $this->cmsAdmin();
        PortfolioProject::factory()->create(['id' => 1]);
        PortfolioCategory::factory()->create(['id' => 1]);
        $this->app['env'] = 'local';

        $this->actingAs($admin)->call($method, $path, ['confirm_delete' => 1])->assertStatus(419);

        $this->assertDatabaseCount('portfolio_projects', 1);
        $this->assertDatabaseCount('portfolio_categories', 1);
    }

    #[DataProvider('mutations')]
    public function test_non_admin_cannot_mutate_portfolio_or_settings(string $method, string $path): void
    {
        $this->cmsAdmin();
        PortfolioProject::factory()->create(['id' => 1]);
        PortfolioCategory::factory()->create(['id' => 1]);

        $this->actingAs(User::factory()->create())->call($method, $path, ['confirm_delete' => 1])->assertForbidden();

        $this->assertDatabaseCount('portfolio_projects', 1);
        $this->assertDatabaseCount('portfolio_categories', 1);
    }
}
