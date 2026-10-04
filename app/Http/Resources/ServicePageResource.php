<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServicePageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'subtitle' => $this->resource->translated('subtitle', app()->getLocale()),
            'lead' => $this->resource->translated('lead', app()->getLocale()),
            'description' => $this->resource->translated('description', app()->getLocale()),
            'heading' => $this->resource->translated('heading', app()->getLocale()),
            'image_alt' => $this->resource->translated('image_alt', app()->getLocale()),
            'cta_label' => $this->resource->translated('cta_label', app()->getLocale()),
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'cta_destination' => $this->cta_destination,
        ];
    }
}
