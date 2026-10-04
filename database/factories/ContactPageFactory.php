<?php

namespace Database\Factories;

use App\Models\ContactPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactPage> */
class ContactPageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'introduction' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'content' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'conversation' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'service_legend' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
        ];
    }
}
