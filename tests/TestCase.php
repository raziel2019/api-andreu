<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\ApprovedContentSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function cmsAdmin(): User
    {
        Storage::fake('public');
        $this->seed(ApprovedContentSeeder::class);

        return User::factory()->administrator()->create();
    }

    protected function contentPayload(Model $page): array
    {
        return Arr::except($page->attributesToArray(), ['id', 'created_at', 'updated_at', 'image_path', 'icon_path', 'logo_path', 'slug']);
    }

    protected function bilingual(string $ca = 'Contingut de prova', string $es = 'Contenido de prueba'): array
    {
        return ['ca' => $ca, 'es' => $es];
    }
}
