<?php

namespace App\Models\Concerns;

trait HasTranslations
{
    public function translated(string $field, string $locale): ?string
    {
        $translations = $this->getAttribute($field) ?? [];

        return $translations[$locale] ?? $translations['ca'] ?? null;
    }
}
