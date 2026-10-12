<?php

namespace App\Http\Requests\Admin;

use App\Rules\CmsDestination;

class UpdatePortfolioPageRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->translations(['title', 'construction_label', 'message']),
            ...$this->translations(['cta_label', 'listing_title', 'eyebrow', 'introduction', 'callout_title', 'callout_content', 'related_title', 'related_eyebrow', 'whatsapp_label'], false),
            'whatsapp_url' => ['sometimes', 'required', 'string', 'max:2048', 'regex:~^https://wa\.me/[0-9]{8,15}$~'],
            'under_construction' => ['required', 'boolean'],
            'cta_destination' => ['nullable', 'string', 'max:2048', new CmsDestination],
            'linkedin_destination' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
