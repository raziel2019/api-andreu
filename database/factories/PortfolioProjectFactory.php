<?php

namespace Database\Factories;

use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioProject> */
class PortfolioProjectFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => ['ca' => 'Projecte de prova', 'es' => 'Proyecto de prueba'], 'slug' => fake()->unique()->slug(), 'sort_order' => 0, 'status' => 'draft', 'body' => ['ca' => '<p>Cos de prova</p>', 'es' => '<p>Cuerpo de prueba</p>']];
    }
}
