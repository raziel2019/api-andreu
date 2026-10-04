<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'subtitle' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'description' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'icon_path' => null,
            'sort_order' => 0,
            'active' => true,
            'slug' => fake()->unique()->slug(),
        ];
    }
}
