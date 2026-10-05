<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\AboutPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['active', 'title', 'introduction', 'image_alt', 'image_caption', 'benefits_heading', 'working_method_heading', 'working_method_introduction', 'image_path'])]
class AboutPage extends Model
{
    /** @use HasFactory<AboutPageFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'title' => 'array',
            'introduction' => 'array',
            'image_alt' => 'array',
            'image_caption' => 'array',
            'benefits_heading' => 'array',
            'working_method_heading' => 'array',
            'working_method_introduction' => 'array',
        ];
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(AboutBenefit::class, 'about_page_id')->orderBy('sort_order')->orderBy('id');
    }

    public function workingMethodSteps(): HasMany
    {
        return $this->hasMany(WorkingMethodStep::class, 'about_page_id')->orderBy('sort_order')->orderBy('id');
    }
}
