<?php

namespace Database\Factories;

use App\Models\LegalPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LegalPage> */
class LegalPageFactory extends Factory
{
    public function definition(): array
    {
        return ['type' => 'legal_notice', 'title' => ['ca' => 'Títol de prova', 'es' => 'Título de prueba'], 'body' => ['ca' => "Contingut de prova\n\nSegon paràgraf", 'es' => "Contenido de prueba\n\nSegundo párrafo"], 'active' => false];
    }
}
