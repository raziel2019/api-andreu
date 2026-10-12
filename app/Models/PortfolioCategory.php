<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'sort_order'])]
class PortfolioCategory extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return ['title' => 'array', 'sort_order' => 'integer'];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class);
    }
}
