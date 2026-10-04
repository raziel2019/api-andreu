<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\HomeServiceSummaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'description', 'icon_path', 'sort_order', 'slug', 'home_page_id'])]
class HomeServiceSummary extends Model
{
    /** @use HasFactory<HomeServiceSummaryFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function homePage(): BelongsTo
    {
        return $this->belongsTo(HomePage::class);
    }
}
