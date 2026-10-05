<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ServicePageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['active', 'title', 'subtitle', 'lead', 'description', 'heading', 'image_alt', 'cta_label', 'image_path', 'cta_destination'])]
class ServicePage extends Model
{
    /** @use HasFactory<ServicePageFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'title' => 'array',
            'subtitle' => 'array',
            'lead' => 'array',
            'description' => 'array',
            'heading' => 'array',
            'image_alt' => 'array',
            'cta_label' => 'array',
        ];
    }
}
