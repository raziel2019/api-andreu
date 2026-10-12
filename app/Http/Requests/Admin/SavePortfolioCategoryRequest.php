<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SavePortfolioCategoryRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->translations(['title']),
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('portfolio_categories')->ignore($this->route('category'))],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
