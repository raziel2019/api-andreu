<?php

namespace Database\Factories;

use App\Models\AboutPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AboutPage> */
class AboutPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'introduction' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'image_alt' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'image_caption' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'benefits_heading' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'working_method_heading' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'working_method_introduction' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'image_path' => null,
        ];
    }
}
