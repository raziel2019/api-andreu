<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AboutPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'introduction' => $this->resource->translated('introduction', app()->getLocale()),
            'image_alt' => $this->resource->translated('image_alt', app()->getLocale()),
            'image_caption' => $this->resource->translated('image_caption', app()->getLocale()),
            'benefits_heading' => $this->resource->translated('benefits_heading', app()->getLocale()),
            'working_method_heading' => $this->resource->translated('working_method_heading', app()->getLocale()),
            'working_method_introduction' => $this->resource->translated('working_method_introduction', app()->getLocale()),
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'benefits' => AboutBenefitResource::collection($this->whenLoaded('benefits')),
            'working_method_steps' => WorkingMethodStepResource::collection($this->whenLoaded('workingMethodSteps')),
        ];
    }
}
