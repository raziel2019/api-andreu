<?php

namespace App\Http\Requests\Admin;

class SaveContactServiceOptionRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->translations(['label']),
            'active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
