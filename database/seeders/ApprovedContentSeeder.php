<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use App\Models\ContactPage;
use App\Models\HomePage;
use App\Models\PortfolioPage;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use App\Services\HomeSummaryIcons;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApprovedContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = json_decode(file_get_contents(database_path('seeders/content/approved-content.json')), true, flags: JSON_THROW_ON_ERROR);
        $translated = function (string $path) use ($content): array {
            return ['ca' => data_get($content['ca'], $path), 'es' => data_get($content['es'], $path)];
        };
        $paragraphs = function (string $path) use ($content): array {
            return [
                'ca' => implode("\n\n", data_get($content['ca'], $path)),
                'es' => implode("\n\n", data_get($content['es'], $path)),
            ];
        };
        foreach (glob(database_path('seeders/media/*')) as $file) {
            $path = 'seed/'.basename($file);
            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, file_get_contents($file));
            }
        }

        DB::transaction(function () use ($content, $translated, $paragraphs): void {
            SiteSetting::firstOrCreate(['id' => 1], [
                'location' => $translated('contact.location'),
                'logo_path' => 'seed/logo.svg',
                'callout_title' => $translated('callout.title'),
                'callout_content' => $translated('callout.body'),
                'callout_label' => $translated('ui.contact'),
                'callout_destination' => '/contacto',
                'footer_description' => $translated('footer.description'),
                'footer_cta_label' => $translated('footer.cta'),
                'footer_cta_destination' => '/contacto',
                'footer_copyright' => $translated('footer.copyright'),
            ]);
            $home = HomePage::firstOrCreate(['id' => 1], [
                'hero_title' => $translated('home.title'),
                'hero_content' => $paragraphs('home.body'),
                'hero_eyebrow' => $translated('home.eyebrow'),
                'hero_image_path' => 'seed/home-hero.png',
                'hero_image_alt' => $translated('home.alt'),
                'hero_image_caption' => $translated('home.caption'),
                'hero_cta_label' => $translated('nav.about'),
                'hero_cta_destination' => '/sobre-mi',
                'services_heading' => $translated('home.servicesTitle'),
                'services_introduction' => $translated('home.servicesSubtitle'),
                'stories_heading' => $translated('home.stories'),
                'stories_introduction' => $translated('home.storiesSubtitle'),
                'portfolio_title' => $translated('home.portfolioTitle'),
                'portfolio_image_path' => 'seed/documents.png',
                'portfolio_image_alt' => $translated('home.portfolioAlt'),
                'portfolio_cta_label' => $translated('home.portfolioCta'),
                'portfolio_cta_destination' => '/portfolio',
                'cta_title' => $translated('callout.title'),
                'cta_content' => $translated('callout.body'),
                'cta_label' => $translated('ui.contact'),
                'cta_destination' => '/contacto',
            ]);
            if ($home->wasRecentlyCreated) {
                $sortOrder = 0;
                foreach (['organization' => 'archive', 'digitization' => 'scan', 'research' => 'search', 'advice' => 'book-open'] as $slug => $icon) {
                    $contentPath = $slug === 'advice' ? 'details.advice' : 'summaries.'.$slug;
                    $home->serviceSummaries()->create([
                        'slug' => $slug,
                        'title' => $translated($contentPath.'.title'),
                        'description' => $translated($contentPath.'.description'),
                        'icon' => $icon,
                        'icon_path' => HomeSummaryIcons::OPTIONS[$icon]['path'],
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
            $about = AboutPage::firstOrCreate(['id' => 1], [
                'title' => $translated('about.title'),
                'introduction' => $paragraphs('about.intro'),
                'image_path' => 'seed/about.png',
                'image_alt' => $translated('about.alt'),
                'image_caption' => $translated('about.caption'),
                'benefits_heading' => $translated('about.benefitsTitle'),
                'working_method_heading' => $translated('about.methodTitle'),
                'working_method_introduction' => $translated('about.methodBody'),
            ]);
            if ($about->wasRecentlyCreated) {
                foreach (['one', 'two', 'three'] as $order => $key) {
                    $about->benefits()->create(['content' => $translated('about.benefits.'.$key), 'sort_order' => $order]);
                    $about->workingMethodSteps()->create(['content' => $translated('about.steps.'.$key), 'sort_order' => $order]);
                }
            }
            ServicePage::firstOrCreate(['id' => 1], [
                'title' => $translated('services.title'),
                'subtitle' => $translated('services.subtitle'),
                'lead' => $translated('services.lead'),
                'description' => $translated('services.description'),
                'heading' => $translated('services.heading'),
                'image_path' => 'seed/documents.png',
                'image_alt' => $translated('services.alt'),
                'cta_label' => $translated('ui.proposal'),
                'cta_destination' => '/contacto',
            ]);
            foreach (['advice', 'organization', 'digitization', 'research'] as $order => $slug) {
                $service = Service::firstOrCreate(['slug' => $slug], [
                    'title' => $translated('details.'.$slug.'.title'),
                    'subtitle' => data_get($content['ca'], 'details.'.$slug.'.subtitle') ? $translated('details.'.$slug.'.subtitle') : null,
                    'description' => $translated('details.'.$slug.'.description'),
                    'icon_path' => 'seed/service-'.$slug.'.svg',
                    'active' => true,
                    'sort_order' => $order,
                ]);
                if ($service->wasRecentlyCreated) {
                    foreach (['one', 'two', 'three'] as $featureOrder => $key) {
                        $service->features()->create(['content' => $translated('details.'.$slug.'.features.'.$key), 'sort_order' => $featureOrder]);
                    }
                }
            }
            PortfolioPage::firstOrCreate(['id' => 1], [
                'title' => $translated('portfolio.title'),
                'under_construction' => true,
                'construction_label' => $translated('portfolio.construction'),
                'message' => $translated('portfolio.body'),
                'cta_label' => $translated('nav.home'),
                'cta_destination' => '/',
            ]);
            $contact = ContactPage::firstOrCreate(['id' => 1], [
                'title' => $translated('contact.title'),
                'introduction' => $translated('contact.intro'),
                'content' => $translated('contact.description'),
                'conversation' => $translated('contact.conversation'),
                'service_legend' => $translated('contact.serviceLegend'),
            ]);
            if ($contact->wasRecentlyCreated) {
                foreach (array_keys($content['ca']['contact']['options']) as $order => $slug) {
                    $contact->serviceOptions()->create([
                        'slug' => $slug, 'label' => $translated('contact.options.'.$slug), 'active' => true, 'sort_order' => $order,
                    ]);
                }
            }
        });
        $this->call(MediaAssetSeeder::class);
    }
}
