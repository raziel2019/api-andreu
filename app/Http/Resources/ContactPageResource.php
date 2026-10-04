<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'introduction' => $this->resource->translated('introduction', app()->getLocale()),
            'content' => $this->resource->translated('content', app()->getLocale()),
            'conversation' => $this->resource->translated('conversation', app()->getLocale()),
            'service_legend' => $this->resource->translated('service_legend', app()->getLocale()),
            'service_options' => ContactServiceOptionResource::collection($this->whenLoaded('serviceOptions')),
        ];
    }
}
