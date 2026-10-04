<?php

namespace App\Http\Requests\Admin;

use App\Rules\CmsDestination;

class UpdatePortfolioPageRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->translations(['title', 'construction_label', 'message']),
            ...$this->translations(['cta_label'], false),
            'under_construction' => ['required', 'boolean'],
            'cta_destination' => ['nullable', 'string', 'max:2048', new CmsDestination],
            'linkedin_destination' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
