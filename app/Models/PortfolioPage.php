<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\PortfolioPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'construction_label', 'message', 'cta_label', 'cta_destination', 'linkedin_destination', 'under_construction'])]
class PortfolioPage extends Model
{
    /** @use HasFactory<PortfolioPageFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'construction_label' => 'array',
            'message' => 'array',
            'cta_label' => 'array',
            'under_construction' => 'boolean',
        ];
    }
}
