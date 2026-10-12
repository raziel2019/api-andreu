<?php

namespace App\Models\Concerns;

use App\Services\RichText;
use Illuminate\Database\Eloquent\Model;

trait HasTranslations
{
    public static function bootHasTranslations(): void
    {
        static::saving(function (Model $model): void {
            foreach (RichText::fields($model) as $field) {
                if (! $model->isDirty($field) || ! is_array($model->getAttribute($field))) {
                    continue;
                }
                $translations = $model->getAttribute($field);
                foreach (['ca', 'es'] as $locale) {
                    $value = $translations[$locale] ?? null;
                    if (is_string($value) && RichText::isHtml($value)) {
                        $translations[$locale] = app(RichText::class)->sanitize($value);
                    }
                }
                $model->setAttribute($field, $translations);
            }
        });
    }

    public function translatedHtml(string $field, string $locale): ?string
    {
        $translations = $this->getAttribute($field) ?? [];

        return app(RichText::class)->html($translations[$locale] ?? $translations['ca'] ?? null);
    }

    public function translated(string $field, string $locale): ?string
    {
        $translations = $this->getAttribute($field) ?? [];

        $value = $translations[$locale] ?? $translations['ca'] ?? null;

        return in_array($field, RichText::fields($this), true) ? app(RichText::class)->text($value) : $value;
    }
}
