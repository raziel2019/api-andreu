<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactServiceOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->resource->translated('label', app()->getLocale()),
            'sort_order' => $this->sort_order,
            'active' => $this->active,
            'slug' => $this->slug,
        ];
    }
}
