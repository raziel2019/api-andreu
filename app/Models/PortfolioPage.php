<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\PortfolioPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['active', 'title', 'construction_label', 'message', 'cta_label', 'cta_destination', 'linkedin_destination', 'under_construction', 'listing_title', 'eyebrow', 'introduction', 'callout_title', 'callout_content', 'related_title', 'related_eyebrow', 'whatsapp_label', 'whatsapp_url'])]
class PortfolioPage extends Model
{
    /** @use HasFactory<PortfolioPageFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'title' => 'array',
            'listing_title' => 'array', 'eyebrow' => 'array', 'introduction' => 'array', 'callout_title' => 'array', 'related_title' => 'array', 'related_eyebrow' => 'array', 'callout_content' => 'array', 'whatsapp_label' => 'array',
            'construction_label' => 'array',
            'message' => 'array',
            'cta_label' => 'array',
            'under_construction' => 'boolean',
        ];
    }
}
