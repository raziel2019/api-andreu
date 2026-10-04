<?php

namespace App\Http\Requests\Admin;

class UpdateAboutPageRequest extends CmsFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->emptyCollections(['benefits', 'working_method_steps']);
    }

    public function rules(): array
    {
        return [
            ...$this->translations(['title', 'introduction', 'image_alt', 'image_caption', 'benefits_heading', 'working_method_heading', 'working_method_introduction']),
            ...$this->imageRules('image'),
            ...$this->repeated('benefits', 'about_benefits', 'about_page_id', 1),
            ...$this->repeated('working_method_steps', 'working_method_steps', 'about_page_id', 1),
        ];
    }
}
