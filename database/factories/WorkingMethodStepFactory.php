<?php

namespace Database\Factories;

use App\Models\AboutPage;
use App\Models\WorkingMethodStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkingMethodStep> */
class WorkingMethodStepFactory extends Factory
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
