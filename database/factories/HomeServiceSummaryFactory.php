<?php

namespace Database\Factories;

use App\Models\HomePage;
use App\Models\HomeServiceSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HomeServiceSummary> */
class HomeServiceSummaryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'description' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'icon_path' => null,
            'icon' => 'archive',
            'sort_order' => 0,
            'slug' => fake()->unique()->slug(),
            'home_page_id' => HomePage::factory(),
        ];
    }
}
