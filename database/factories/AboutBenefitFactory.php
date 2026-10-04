<?php

namespace Database\Factories;

use App\Models\AboutBenefit;
use App\Models\AboutPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AboutBenefit> */
class AboutBenefitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'sort_order' => 0,
            'about_page_id' => AboutPage::factory(),
        ];
    }
}
