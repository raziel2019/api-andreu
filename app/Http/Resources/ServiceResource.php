<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'subtitle' => $this->resource->translated('subtitle', app()->getLocale()),
            'description' => $this->resource->translated('description', app()->getLocale()),
            'icon_url' => $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null,
            'sort_order' => $this->sort_order,
            'active' => $this->active,
            'slug' => $this->slug,
            'features' => ServiceFeatureResource::collection($this->whenLoaded('features')),
        ];
    }
}
