<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HomeServiceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'description' => $this->resource->translated('description', app()->getLocale()),
            'icon_url' => $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null,
            'sort_order' => $this->sort_order,
            'slug' => $this->slug,
        ];
    }
}
