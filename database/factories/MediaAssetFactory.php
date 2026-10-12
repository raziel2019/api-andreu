<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MediaAsset> */
class MediaAssetFactory extends Factory
{
    public function definition(): array
    {
        return ['disk' => 'public', 'path' => 'cms/'.fake()->uuid().'.png', 'original_filename' => 'image.png', 'mime_type' => 'image/png', 'size' => 100, 'width' => 10, 'height' => 10, 'alt' => ['ca' => 'Imatge', 'es' => 'Imagen']];
    }
}
