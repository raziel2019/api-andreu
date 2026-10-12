<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'description', 'body', 'slug', 'portfolio_category_id', 'featured_image_path', 'featured_image_alt', 'gallery', 'year', 'client', 'status', 'sort_order'])]
class PortfolioProject extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array', 'body' => 'array', 'featured_image_alt' => 'array', 'gallery' => 'array', 'year' => 'integer', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PortfolioCategory::class, 'portfolio_category_id');
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
