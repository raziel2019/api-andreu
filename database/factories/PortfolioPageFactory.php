<?php

namespace Database\Factories;

use App\Models\PortfolioPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PortfolioPage> */
class PortfolioPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'construction_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'message' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'cta_label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'cta_destination' => null,
            'linkedin_destination' => null,
            'under_construction' => true,
        ];
    }
}
