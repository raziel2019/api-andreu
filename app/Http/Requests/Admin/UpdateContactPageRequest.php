<?php

namespace App\Http\Requests\Admin;

class UpdateContactPageRequest extends CmsFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->translations(['title', 'introduction', 'content', 'conversation', 'service_legend']),

        ];
    }
}
