<?php

namespace Tests\Feature;

use App\Models\AboutPage;
use App\Models\ContactPage;
use App\Models\HomePage;
use App\Models\LegalPage;
use App\Models\PortfolioPage;
use App\Models\PortfolioProject;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\RichText;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RichTextCmsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_supported_formatting_survives_sanitization_without_double_escaping(): void
    {
        $html = '<h2>Arxiu &amp; memòria</h2><h3>Context</h3><p><strong>Negreta</strong> <em>Cursiva</em> <u>Subratllat</u><br>Text</p><ul><li>Un</li></ul><ol><li>Dos</li></ol><blockquote><p>Citació</p></blockquote><p><a href="/contacte">Contacte</a></p>';
        $sanitizer = app(RichText::class);

        $this->assertSame($html, $sanitizer->sanitize($html));
        $this->assertSame($html, $sanitizer->sanitize($sanitizer->sanitize($html)));
        $this->assertSame('<p><strong>B</strong><em>I</em></p>', $sanitizer->sanitize('<p><b>B</b><i>I</i></p>'));
    }

    public static function links(): array
    {
        return [
            'https' => ['https://example.test/path?q=1', true],
            'http' => ['http://example.test', true],
            'email' => ['mailto:andreu@example.test', true],
            'root relative' => ['/contacte?locale=ca#formulari', true],
            'document relative' => ['../contacte', true],
            'plain relative' => ['contacte', true],
            'fragment' => ['#context', true],
            'query' => ['?locale=es', true],
            'javascript' => ['javascript:alert(1)', false],
            'encoded javascript' => ['&#106;avascript:alert(1)', false],
            'control characters' => ["java\tscript:alert(1)", false],
            'data' => ['data:text/html,test', false],
            'network path' => ['//evil.test', false],
            'backslash' => ['/\\evil.test', false],
            'ftp' => ['ftp://example.test', false],
            'tel' => ['tel:123456', false],
        ];
    }

    #[DataProvider('links')]
    public function test_only_safe_link_schemes_and_relative_paths_are_retained(string $href, bool $allowed): void
    {
        $html = app(RichText::class)->sanitize('<p><a href="'.$href.'" target="_blank" onclick="evil()">Enllaç</a></p>');

        $this->assertSame($allowed ? '<p><a href="'.$href.'">Enllaç</a></p>' : '<p><a>Enllaç</a></p>', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function attacks(): array
    {
        return [
            'script' => ['<script>alert(1)</script>'],
            'iframe' => ['<iframe src="https://evil.test">evil</iframe>'],
            'svg' => ['<svg onload="alert(1)"><a>evil</a></svg>'],
            'image' => ['<img src="x" onerror="alert(1)">'],
            'style' => ['<style>body{display:none}</style>'],
            'widget' => ['<object data="evil"><embed src="evil"></object>'],
            'math' => ['<math><mtext>evil</mtext></math>'],
            'template' => ['<template><script>evil</script></template>'],
            'form' => ['<form action="evil"><input name="csrf">evil</form>'],
        ];
    }

    #[DataProvider('attacks')]
    public function test_active_elements_and_their_contents_are_removed(string $attack): void
    {
        $this->assertSame('<p>Segur</p>', app(RichText::class)->sanitize($attack.'<p style="color:red" onclick="evil()" data-test="x">Segur</p>'));
    }

    public static function forms(): array
    {
        return [
            'home' => [HomePage::class, '/admin/home', 'hero_content', '/api/v1/pages/home', 'data.hero_content'],
            'about' => [AboutPage::class, '/admin/about', 'introduction', '/api/v1/pages/about', 'data.introduction'],
            'services page' => [ServicePage::class, '/admin/services/page', 'description', '/api/v1/services', 'meta.page.description'],
            'portfolio listing' => [PortfolioPage::class, '/admin/portfolio', 'introduction', '/api/v1/pages/portfolio', 'data.introduction'],
            'contact' => [ContactPage::class, '/admin/contact', 'content', '/api/v1/pages/contact', 'data.content'],
            'global' => [SiteSetting::class, '/admin/settings', 'callout_content', '/api/v1/site', 'data.callout_content'],
        ];
    }

    #[DataProvider('forms')]
    public function test_existing_page_forms_save_rich_text_and_api_keeps_plain_text_consumers(string $modelClass, string $form, string $field, string $api, string $path): void
    {
        $admin = $this->cmsAdmin();
        $page = $modelClass::findOrFail(1);
        $payload = $this->contentPayload($page);
        foreach (['service_summaries', 'benefits', 'working_method_steps', 'service_options'] as $collection) {
            $payload[$collection] = [];
        }
        $payload[$field] = ['ca' => '<h2>Arxiu</h2><p><strong>CA</strong><script>evil()</script></p>', 'es' => '<p><em>ES</em></p>'];

        $this->actingAs($admin)->put($form, $payload)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(['ca' => '<h2>Arxiu</h2><p><strong>CA</strong></p>', 'es' => '<p><em>ES</em></p>'], $page->fresh()->getAttribute($field));
        $this->getJson($api)->assertOk()->assertJsonPath($path, "Arxiu\n\nCA")->assertJsonPath($path.'_html', '<h2>Arxiu</h2><p><strong>CA</strong></p>');
        $this->getJson($api.'?locale=es')->assertJsonPath($path, 'ES')->assertJsonPath($path.'_html', '<p><em>ES</em></p>');
        $this->get($form)->assertSee('data-rich-text', false)->assertSee('name="'.$field.'[ca]"', false)->assertSee('name="'.$field.'[es]"', false);
    }

    public function test_repeatable_content_and_service_descriptions_save_and_serialize_rich_text(): void
    {
        $admin = $this->cmsAdmin();
        $about = AboutPage::findOrFail(1);
        $payload = $this->contentPayload($about);
        $payload['benefits'] = [['content' => $this->bilingual('<p><u>CA</u></p>', '<h3>ES</h3>'), 'sort_order' => 0]];
        $payload['working_method_steps'] = [['content' => $this->bilingual('<ul><li>CA</li></ul>', '<ol><li>ES</li></ol>'), 'sort_order' => 0]];

        $this->actingAs($admin)->put('/admin/about', $payload)->assertSessionHasNoErrors();

        $this->getJson('/api/v1/pages/about')->assertJsonPath('data.benefits.0.content', 'CA')->assertJsonPath('data.benefits.0.content_html', '<p><u>CA</u></p>')->assertJsonPath('data.working_method_steps.0.content_html', '<ul><li>CA</li></ul>');
        $service = Service::firstOrFail();
        $this->put('/admin/services/'.$service->id, [...$this->contentPayload($service), 'slug' => $service->slug, 'description' => $this->bilingual('<p><strong>CA</strong></p>', '<p>ES</p>'), 'features' => [['content' => $this->bilingual('<blockquote><p>CA</p></blockquote>', '<p>ES</p>'), 'sort_order' => 0]]])->assertSessionHasNoErrors();
        $this->getJson('/api/v1/services')->assertJsonPath('data.0.description', 'CA')->assertJsonPath('data.0.description_html', '<p><strong>CA</strong></p>')->assertJsonPath('data.0.features.0.content_html', '<blockquote><p>CA</p></blockquote>');
    }

    public function test_project_description_and_detail_body_keep_the_existing_api_semantics(): void
    {
        $admin = $this->cmsAdmin();
        PortfolioPage::findOrFail(1)->update(['under_construction' => false]);
        $payload = ['title' => $this->bilingual(), 'description' => $this->bilingual('<p><strong>CA</strong></p>', '<p><em>ES</em></p>'), 'body' => $this->bilingual('<h2>Context</h2><p><a href="/contacte">Contacte</a></p>', '<h3>Contexto</h3>'), 'slug' => 'arxiu', 'status' => 'published', 'sort_order' => 0, 'gallery' => []];

        $this->actingAs($admin)->post('/admin/portfolio/projects', $payload)->assertSessionHasNoErrors();

        $this->getJson('/api/v1/projects/arxiu')->assertJsonPath('data.description', 'CA')->assertJsonPath('data.description_html', '<p><strong>CA</strong></p>')->assertJsonPath('data.body', '<h2>Context</h2><p><a href="/contacte">Contacte</a></p>');
        $this->getJson('/api/v1/projects/arxiu?locale=es')->assertJsonPath('data.body', '<h3>Contexto</h3>');
    }

    public function test_validation_errors_keep_unsaved_rich_text_in_both_locales(): void
    {
        $admin = $this->cmsAdmin();
        $page = ContactPage::findOrFail(1);
        $payload = [...$this->contentPayload($page), 'title' => ['ca' => null, 'es' => null], 'content' => $this->bilingual('<p><strong>Nou CA</strong></p>', '<h3>Nuevo ES</h3>')];

        $this->actingAs($admin)->from('/admin/contact')->put('/admin/contact', $payload)->assertSessionHasErrors('title.ca')->assertSessionHasInput('content.ca', '<p><strong>Nou CA</strong></p>')->assertSessionHasInput('content.es', '<h3>Nuevo ES</h3>');

        $this->assertSame($page->content, $page->fresh()->content);
        $this->get('/admin/contact')->assertSee('<p><strong>Nou CA</strong></p>')->assertSee('<h3>Nuevo ES</h3>');
    }

    public function test_empty_or_unsafe_only_html_cannot_publish_legal_text_and_input_is_retained(): void
    {
        $page = LegalPage::factory()->create();
        $payload = ['title' => $page->title, 'body' => ['ca' => '<script>evil()</script>', 'es' => '<p><br></p>'], 'active' => 1];

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/legal/legal-notice', $payload)->assertSessionHasErrors(['body.ca', 'body.es'])->assertSessionHasInput('body.ca', '<script>evil()</script>');

        $this->assertSame($page->body, $page->fresh()->body);
        $this->assertFalse($page->fresh()->active);
    }

    public function test_legacy_plain_text_and_existing_html_are_safe_without_a_data_migration(): void
    {
        $page = LegalPage::factory()->create(['active' => true, 'body' => ['ca' => "Un & dos\r\nLínia\r\n\r\nParàgraf", 'es' => 'Texto']]);
        $before = $page->getRawOriginal('body');

        $this->getJson('/api/v1/legal/legal-notice')->assertJsonPath('data.body', "Un & dos\r\nLínia\r\n\r\nParàgraf")->assertJsonPath('data.body_html', '<p>Un &amp; dos<br>Línia</p><p>Paràgraf</p>');

        $this->assertSame($before, $page->fresh()->getRawOriginal('body'));
        DB::table('legal_pages')->where('id', $page->id)->update(['body' => json_encode(['ca' => '<p onclick="evil()"><strong>Text</strong></p><script>evil()</script>', 'es' => '<p>ES</p>'])]);
        $this->getJson('/api/v1/legal/legal-notice')->assertJsonPath('data.body', 'Text')->assertJsonPath('data.body_html', '<p><strong>Text</strong></p>');
    }

    public function test_seeding_preserves_edited_rich_content(): void
    {
        $this->cmsAdmin();
        $home = HomePage::findOrFail(1);
        $content = ['ca' => '<p><strong>Canvi CA</strong></p>', 'es' => '<p><em>Cambio ES</em></p>'];
        $home->update(['hero_content' => $content]);

        $this->seed(ApprovedContentSeeder::class);

        $this->assertSame($content, $home->fresh()->hero_content);
    }

    public static function legalSections(): array
    {
        return [['legal-notice', 'legal_notice'], ['privacy', 'privacy_policy'], ['cookies', 'cookie_policy']];
    }

    #[DataProvider('legalSections')]
    public function test_legal_pages_save_and_expose_formatted_text_in_both_languages(string $section, string $type): void
    {
        $page = LegalPage::factory()->create(['type' => $type]);
        $payload = ['title' => $page->title, 'body' => ['ca' => '<h2>Avís</h2><p><a href="mailto:privacy@example.test">Contacte</a></p>', 'es' => '<h3>Aviso</h3><p><u>Privacidad</u></p>'], 'active' => 1];

        $this->actingAs(User::factory()->administrator()->create())->put('/admin/legal/'.$section, $payload)->assertSessionHasNoErrors()->assertRedirect();

        $this->getJson('/api/v1/legal/'.$section)->assertJsonPath('data.body', "Avís\n\nContacte")->assertJsonPath('data.body_html', '<h2>Avís</h2><p><a href="mailto:privacy&#64;example.test">Contacte</a></p>');
        $this->getJson('/api/v1/legal/'.$section.'?locale=es')->assertJsonPath('data.body', "Aviso\n\nPrivacidad")->assertJsonPath('data.body_html', '<h3>Aviso</h3><p><u>Privacidad</u></p>');
        $this->assertSame('<h3>Aviso</h3><p><u>Privacidad</u></p>', $page->fresh()->body['es']);
    }

    public function test_home_summary_rich_description_is_saved_through_the_existing_nested_form(): void
    {
        $admin = $this->cmsAdmin();
        $home = HomePage::findOrFail(1);
        $payload = $this->contentPayload($home);
        $payload['service_summaries'] = [['title' => $this->bilingual(), 'slug' => 'arxiu', 'description' => $this->bilingual('<p><strong>CA</strong></p>', '<p><em>ES</em></p>'), 'sort_order' => 0, 'icon' => 'archive']];

        $this->actingAs($admin)->put('/admin/home', $payload)->assertSessionHasNoErrors();

        $this->getJson('/api/v1/pages/home')->assertJsonPath('data.service_summaries.0.description', 'CA')->assertJsonPath('data.service_summaries.0.description_html', '<p><strong>CA</strong></p>');
        $this->getJson('/api/v1/pages/home?locale=es')->assertJsonPath('data.service_summaries.0.description_html', '<p><em>ES</em></p>');
    }

    public function test_malformed_markup_is_normalized_and_legacy_wrappers_keep_text(): void
    {
        $sanitizer = app(RichText::class);

        $this->assertSame('<p><strong>Text</strong></p>', $sanitizer->sanitize('<div><p><strong>Text'));
        $this->assertSame('<p>Word <em>text</em></p>', $sanitizer->sanitize('<div><p><span style="color:red">Word </span><i>text</i></p></div>'));
    }

    public function test_gallery_alt_remains_plain_text_without_an_editor(): void
    {
        $admin = $this->cmsAdmin();
        $project = PortfolioProject::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/portfolio/projects/'.$project->id.'/edit');

        $response->assertSee('data-rich-text', false)->assertSee('name="featured_image_alt[ca]"', false);
        $this->assertStringNotContainsString('data-source', substr($response->getContent(), strpos($response->getContent(), 'id="featured_image_alt-ca"'), 200));
    }
}
