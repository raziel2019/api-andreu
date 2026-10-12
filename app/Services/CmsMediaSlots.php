<?php

namespace App\Services;

use App\Models\AboutPage;
use App\Models\HomePage;
use App\Models\HomeServiceSummary;
use App\Models\Service;
use App\Models\ServiceFeature;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;

class CmsMediaSlots
{
    /** @return array<string, array{label: string, record: Model, column: string}> */
    public function all(): array
    {
        $slots = [];
        $definitions = [
            'home-hero' => [HomePage::class, 'hero_image_path', 'Biblioteca · Inicio'],
            'services-image' => [ServicePage::class, 'image_path', 'Documentos · Servicios'],
            'home-portfolio' => [HomePage::class, 'portfolio_image_path', 'Portfolio · Inicio'],
            'about-image' => [AboutPage::class, 'image_path', 'Libros · Sobre mí'],
            'site-logo' => [SiteSetting::class, 'logo_path', 'Logotipo · Identidad'],
        ];
        $records = [];
        foreach ($definitions as $key => [$model, $column, $label]) {
            $records[$model] ??= $model::find(1);
            if ($records[$model]) {
                $slots[$key] = ['label' => $label, 'record' => $records[$model], 'column' => $column];
            }
        }
        foreach (Service::orderBy('sort_order')->orderBy('id')->get() as $service) {
            $slots['service-'.$service->id] = ['label' => 'Icono · '.$service->translated('title', 'ca'), 'record' => $service, 'column' => 'icon_path'];
        }
        foreach (HomeServiceSummary::orderBy('sort_order')->orderBy('id')->get() as $summary) {
            $slots['summary-'.$summary->id] = ['label' => 'Resumen · '.$summary->translated('title', 'ca'), 'record' => $summary, 'column' => 'icon_path'];
        }

        foreach (ServiceFeature::orderBy('service_id')->orderBy('sort_order')->orderBy('id')->get() as $feature) {
            $slots['feature-'.$feature->id] = ['label' => 'Característica · '.$feature->translated('content', 'ca'), 'record' => $feature, 'column' => 'icon_path'];
        }
        if ($settings = $records[SiteSetting::class] ?? null) {
            foreach (InterfaceMediaSlots::LABELS as $key => $label) {
                $slots['interface-'.$key] = ['label' => $label, 'record' => $settings, 'column' => 'interface_media->'.$key];
            }
        }

        return $slots;
    }

    /** @param array{label: string, record: Model, column: string} $slot */
    public static function path(array $slot): ?string
    {
        return data_get($slot['record'], str_replace('->', '.', $slot['column']));
    }

    /** @return array{label: string, record: Model, column: string} */
    public function find(string $key): array
    {
        $slots = $this->all();
        abort_unless(isset($slots[$key]), 404);

        return $slots[$key];
    }
}
