<?php

namespace App\Http\Resources;

use App\Services\CmsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'introduction' => $this->resource->translated('introduction', app()->getLocale()),
            'introduction_html' => $this->resource->translatedHtml('introduction', app()->getLocale()),
            'image_alt' => $this->resource->translated('image_alt', app()->getLocale()),
            'image_caption' => $this->resource->translated('image_caption', app()->getLocale()),
            'benefits_heading' => $this->resource->translated('benefits_heading', app()->getLocale()),
            'working_method_heading' => $this->resource->translated('working_method_heading', app()->getLocale()),
            'working_method_introduction' => $this->resource->translated('working_method_introduction', app()->getLocale()),
            'working_method_introduction_html' => $this->resource->translatedHtml('working_method_introduction', app()->getLocale()),
            'image_url' => CmsMedia::publicUrl($this->image_path),
            'benefits' => AboutBenefitResource::collection($this->whenLoaded('benefits')),
            'working_method_steps' => WorkingMethodStepResource::collection($this->whenLoaded('workingMethodSteps')),
        ];
    }
}
