<?php

namespace Database\Factories;

use App\Models\ServicePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServicePage> */
class ServicePageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'subtitle' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'lead' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'description' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'heading' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'image_alt' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'cta_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'image_path' => null,
            'cta_destination' => null,
        ];
    }
}
