<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdatePortfolioProjectOrderRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['array:id,sort_order'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('portfolio_projects', 'id')],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
