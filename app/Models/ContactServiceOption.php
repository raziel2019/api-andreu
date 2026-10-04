<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ContactServiceOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['label', 'sort_order', 'active', 'slug', 'contact_page_id'])]
class ContactServiceOption extends Model
{
    /** @use HasFactory<ContactServiceOptionFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function contactPage(): BelongsTo
    {
        return $this->belongsTo(ContactPage::class);
    }
}
