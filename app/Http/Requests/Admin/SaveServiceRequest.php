<?php

namespace App\Http\Requests\Admin;

class SaveServiceRequest extends CmsFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->emptyCollections(['features']);
    }

    public function rules(): array
    {
        return [
            ...$this->translations(['title', 'description']),
            ...$this->translations(['subtitle'], false),
            ...$this->imageRules('icon'),
            'active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            ...$this->repeated('features', 'service_features', 'service_id', $this->route('service')?->getKey() ?? 0),
        ];
    }
}
