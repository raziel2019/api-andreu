<?php

namespace App\Http\Requests\Admin;

use App\Services\CmsMediaSlots;

class UpdateMediaSlotRequest extends CmsFormRequest
{
    public function rules(CmsMediaSlots $slots): array
    {
        $slots->find((string) $this->route('slot'));
        $rules = $this->imageRules('image');
        $rules['image'][0] = $this->boolean('remove_image') ? 'nullable' : 'required_without:image_asset_id';

        return $rules;
    }
}
