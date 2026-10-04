<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingMethodStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->resource->translated('content', app()->getLocale()),
            'sort_order' => $this->sort_order,
        ];
    }
}
