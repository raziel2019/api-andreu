<?php

namespace App\Http\Resources;

use App\Services\CmsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'subtitle' => $this->resource->translated('subtitle', app()->getLocale()),
            'description' => $this->resource->translated('description', app()->getLocale()),
            'icon_url' => CmsMedia::publicUrl($this->icon_path),
            'sort_order' => $this->sort_order,
            'active' => $this->active,
            'slug' => $this->slug,
            'features' => ServiceFeatureResource::collection($this->whenLoaded('features')),
        ];
    }
}
