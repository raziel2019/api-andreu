<?php

namespace Database\Factories;

use App\Models\ContactPage;
use App\Models\ContactServiceOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactServiceOption> */
class ContactServiceOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => ['ca' => 'Text de prova', 'es' => 'Texto de prueba'],
            'sort_order' => 0,
            'active' => true,
            'slug' => fake()->unique()->slug(),
            'contact_page_id' => ContactPage::factory(),
        ];
    }
}
