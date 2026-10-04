<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteSetting> */
class SiteSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'callout_title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'callout_content' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'callout_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'footer_description' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'footer_cta_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'footer_copyright' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'contact_email' => null,
            'phone' => null,
            'linkedin_url' => null,
            'instagram_url' => null,
            'logo_path' => null,
            'callout_destination' => null,
            'footer_cta_destination' => null,
        ];
    }
}
