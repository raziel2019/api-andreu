<?php

namespace Database\Factories;

use App\Models\HomePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HomePage> */
class HomePageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hero_title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'hero_content' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'hero_eyebrow' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'hero_image_alt' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'hero_image_caption' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'hero_cta_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'services_heading' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'services_introduction' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'stories_heading' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'stories_introduction' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'portfolio_title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'portfolio_image_alt' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'portfolio_cta_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'cta_title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'cta_content' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'cta_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'hero_image_path' => null,
            'hero_cta_destination' => null,
            'portfolio_image_path' => null,
            'portfolio_cta_destination' => null,
            'cta_destination' => null,
        ];
    }
}
