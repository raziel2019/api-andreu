<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceFeature> */
class ServiceFeatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'sort_order' => 0,
            'service_id' => Service::factory(),
        ];
    }
}
