<?php

namespace App\Http\Requests\Admin;

use App\Rules\CmsDestination;

class UpdateServicePageRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->translations(['title', 'subtitle', 'lead', 'description', 'heading', 'image_alt', 'cta_label']),
            ...$this->imageRules('image'),
            'cta_destination' => ['required', 'string', 'max:2048', new CmsDestination],
        ];
    }
}
