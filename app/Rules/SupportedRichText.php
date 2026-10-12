<?php

namespace App\Rules;

use App\Services\RichText;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportedRichText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && RichText::isHtml($value) && trim(str_replace("\u{00a0}", ' ', app(RichText::class)->text($value) ?? '')) === '') {
            $fail('El contingut HTML ha de contenir text segur.');
        }
    }
}
