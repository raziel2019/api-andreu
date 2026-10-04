<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'subtitle', 'description', 'icon_path', 'sort_order', 'active', 'slug'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'description' => 'array',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(ServiceFeature::class, 'service_id')->orderBy('sort_order')->orderBy('id');
    }
}
