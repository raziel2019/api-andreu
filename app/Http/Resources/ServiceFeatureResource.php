<?php

namespace App\Http\Resources;

use App\Services\CmsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceFeatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->resource->translated('content', app()->getLocale()),
            'content_html' => $this->resource->translatedHtml('content', app()->getLocale()),
            'sort_order' => $this->sort_order,
            'icon_url' => CmsMedia::publicUrl($this->icon_path),
        ];
    }
}
