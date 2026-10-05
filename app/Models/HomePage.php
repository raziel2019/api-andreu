<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\HomePageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['active', 'hero_title', 'hero_content', 'hero_eyebrow', 'hero_image_alt', 'hero_image_caption', 'hero_cta_label', 'services_heading', 'services_introduction', 'stories_heading', 'stories_introduction', 'portfolio_title', 'portfolio_image_alt', 'portfolio_cta_label', 'cta_title', 'cta_content', 'cta_label', 'hero_image_path', 'hero_cta_destination', 'portfolio_image_path', 'portfolio_cta_destination', 'cta_destination'])]
class HomePage extends Model
{
    /** @use HasFactory<HomePageFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'hero_title' => 'array',
            'hero_content' => 'array',
            'hero_eyebrow' => 'array',
            'hero_image_alt' => 'array',
            'hero_image_caption' => 'array',
            'hero_cta_label' => 'array',
            'services_heading' => 'array',
            'services_introduction' => 'array',
            'stories_heading' => 'array',
            'stories_introduction' => 'array',
            'portfolio_title' => 'array',
            'portfolio_image_alt' => 'array',
            'portfolio_cta_label' => 'array',
            'cta_title' => 'array',
            'cta_content' => 'array',
            'cta_label' => 'array',
        ];
    }

    public function serviceSummaries(): HasMany
    {
        return $this->hasMany(HomeServiceSummary::class, 'home_page_id')->orderBy('sort_order')->orderBy('id');
    }
}
