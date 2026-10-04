<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\LegalPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'title', 'body', 'active'])]
class LegalPage extends Model
{
    /** @use HasFactory<LegalPageFactory> */
    use HasFactory, HasTranslations;

    public const TYPES = ['legal-notice' => 'legal_notice', 'privacy' => 'privacy_policy', 'cookies' => 'cookie_policy'];

    protected function casts(): array
    {
        return ['title' => 'array', 'body' => 'array', 'active' => 'boolean'];
    }
}
