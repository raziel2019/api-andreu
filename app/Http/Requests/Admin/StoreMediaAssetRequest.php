<?php

namespace App\Http\Requests\Admin;

class StoreMediaAssetRequest extends CmsFormRequest
{
    public function rules(): array
    {
        $rules = $this->imageRules('image');
        $rules['image'][0] = 'required';

        return ['image' => $rules['image'], ...$this->translations(['alt'], false),
            'alt.ca' => ['required_with:alt.ca,alt.es', 'nullable', 'string', 'max:20000'],
            'alt.es' => ['required_with:alt.ca,alt.es', 'nullable', 'string', 'max:20000'],
        ];
    }
}
