<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CmsDestination implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Indica una ruta interna o un URL HTTP/HTTPS vàlid.');

            return;
        }
        $internal = preg_match('~^/(?!/)[a-zA-Z0-9/_?&=.%#-]*$~D', $value) === 1;
        $external = filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
        if (! $internal && ! $external) {
            $fail('Indica una ruta interna o un URL HTTP/HTTPS vàlid.');
        }
    }
}
