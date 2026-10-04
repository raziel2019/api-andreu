<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'construction_label' => $this->resource->translated('construction_label', app()->getLocale()),
            'message' => $this->resource->translated('message', app()->getLocale()),
            'cta_label' => $this->resource->translated('cta_label', app()->getLocale()),
            'cta_destination' => $this->cta_destination,
            'linkedin_destination' => $this->linkedin_destination,
            'under_construction' => $this->under_construction,
        ];
    }
}
