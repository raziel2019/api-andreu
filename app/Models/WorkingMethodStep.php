<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\WorkingMethodStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['content', 'sort_order', 'about_page_id'])]
class WorkingMethodStep extends Model
{
    /** @use HasFactory<WorkingMethodStepFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function aboutPage(): BelongsTo
    {
        return $this->belongsTo(AboutPage::class);
    }
}
