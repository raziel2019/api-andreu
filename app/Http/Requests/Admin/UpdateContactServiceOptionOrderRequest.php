<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateContactServiceOptionOrderRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*' => ['array:id,sort_order'],
            'items.*.id' => ['required', 'integer', 'distinct', Rule::exists('contact_service_options', 'id')->where('contact_page_id', 1)],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
