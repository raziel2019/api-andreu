<?php

namespace App\Http\Requests\Admin;

class UpdateAppearanceRequest extends CmsFormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['primary_color', 'background_color'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtoupper($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'primary_color' => ['required', 'string', 'regex:/\A#[0-9A-F]{6}\z/'],
            'background_color' => ['required', 'string', 'regex:/\A#[0-9A-F]{6}\z/'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.*' => 'Indica el color principal con seis dígitos hexadecimales (#RRGGBB).',
            'background_color.*' => 'Indica el fondo con seis dígitos hexadecimales (#RRGGBB).',
        ];
    }
}
