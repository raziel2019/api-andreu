<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\ContactPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['active', 'title', 'introduction', 'content', 'conversation', 'service_legend'])]
class ContactPage extends Model
{
    /** @use HasFactory<ContactPageFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'title' => 'array',
            'introduction' => 'array',
            'content' => 'array',
            'conversation' => 'array',
            'service_legend' => 'array',
        ];
    }

    public function serviceOptions(): HasMany
    {
        return $this->hasMany(ContactServiceOption::class, 'contact_page_id')->orderBy('sort_order')->orderBy('id');
    }
}
