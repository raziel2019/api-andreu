<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SaveServiceRequest extends CmsFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->emptyCollections(['features']);
    }

    public function rules(): array
    {
        $features = $this->repeated('features', 'service_features', 'service_id', $this->route('service')?->getKey() ?? 0);
        $features['features.*'] = ['array:id,content,sort_order,icon,icon_asset_id,remove_icon'];

        return [
            ...$this->translations(['title', 'description']),
            ...$this->translations(['subtitle'], false),
            ...$this->imageRules('icon'),
            'active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
            ...$features,
            'features.*.icon' => $this->imageRules('icon')['icon'],
            'features.*.icon_asset_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->where('disk', 'public')],
            'features.*.remove_icon' => ['sometimes', 'boolean'],
        ];
    }
}
