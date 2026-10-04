<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HomePageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hero_title' => $this->resource->translated('hero_title', app()->getLocale()),
            'hero_content' => $this->resource->translated('hero_content', app()->getLocale()),
            'hero_eyebrow' => $this->resource->translated('hero_eyebrow', app()->getLocale()),
            'hero_image_alt' => $this->resource->translated('hero_image_alt', app()->getLocale()),
            'hero_image_caption' => $this->resource->translated('hero_image_caption', app()->getLocale()),
            'hero_cta_label' => $this->resource->translated('hero_cta_label', app()->getLocale()),
            'services_heading' => $this->resource->translated('services_heading', app()->getLocale()),
            'services_introduction' => $this->resource->translated('services_introduction', app()->getLocale()),
            'stories_heading' => $this->resource->translated('stories_heading', app()->getLocale()),
            'stories_introduction' => $this->resource->translated('stories_introduction', app()->getLocale()),
            'portfolio_title' => $this->resource->translated('portfolio_title', app()->getLocale()),
            'portfolio_image_alt' => $this->resource->translated('portfolio_image_alt', app()->getLocale()),
            'portfolio_cta_label' => $this->resource->translated('portfolio_cta_label', app()->getLocale()),
            'cta_title' => $this->resource->translated('cta_title', app()->getLocale()),
            'cta_content' => $this->resource->translated('cta_content', app()->getLocale()),
            'cta_label' => $this->resource->translated('cta_label', app()->getLocale()),
            'hero_image_url' => $this->hero_image_path ? Storage::disk('public')->url($this->hero_image_path) : null,
            'hero_cta_destination' => $this->hero_cta_destination,
            'portfolio_image_url' => $this->portfolio_image_path ? Storage::disk('public')->url($this->portfolio_image_path) : null,
            'portfolio_cta_destination' => $this->portfolio_cta_destination,
            'cta_destination' => $this->cta_destination,
            'service_summaries' => HomeServiceSummaryResource::collection($this->whenLoaded('serviceSummaries')),
        ];
    }
}
