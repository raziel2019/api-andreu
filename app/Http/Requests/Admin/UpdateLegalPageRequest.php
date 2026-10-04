<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Validation\Rule;

class UpdateLegalPageRequest extends CmsFormRequest
{
    public function rules(): array
    {
        $plainText = function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && preg_match('/<\/?[a-z!][^>]*>/i', $value)) {
                $fail('Utilitza text pla, sense etiquetes HTML.');
            }
        };

        return [
            'title' => ['required', 'array:ca,es'],
            'title.ca' => ['required', 'string', 'max:255', $plainText],
            'title.es' => ['required', 'string', 'max:255', $plainText],
            'body' => ['required', 'array:ca,es'],
            'body.ca' => ['present', Rule::requiredIf($this->boolean('active')), 'nullable', 'string', 'max:100000', $plainText],
            'body.es' => ['present', Rule::requiredIf($this->boolean('active')), 'nullable', 'string', 'max:100000', $plainText],
            'active' => ['required', 'boolean'],
        ];
    }
}
