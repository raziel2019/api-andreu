<?php

namespace App\Http\Resources;

use App\Services\CmsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeServiceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'description' => $this->resource->translated('description', app()->getLocale()),
            'description_html' => $this->resource->translatedHtml('description', app()->getLocale()),
            'icon' => $this->icon,
            'icon_url' => CmsMedia::publicUrl($this->icon_path),
            'sort_order' => $this->sort_order,
            'slug' => $this->slug,
        ];
    }
}
