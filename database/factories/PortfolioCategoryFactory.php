<?php

namespace Database\Factories;

use App\Models\PortfolioCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioCategory> */
class PortfolioCategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => ['ca' => 'Projecte de prova', 'es' => 'Proyecto de prueba'], 'slug' => fake()->unique()->slug(), 'sort_order' => 0];
    }
}
