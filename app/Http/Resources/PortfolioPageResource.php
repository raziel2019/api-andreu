<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'listing_title' => $this->resource->translated('listing_title', app()->getLocale()),
            'eyebrow' => $this->resource->translated('eyebrow', app()->getLocale()),
            'introduction' => $this->resource->translated('introduction', app()->getLocale()),
            'introduction_html' => $this->resource->translatedHtml('introduction', app()->getLocale()),
            'callout_title' => $this->resource->translated('callout_title', app()->getLocale()),
            'callout_content' => $this->resource->translated('callout_content', app()->getLocale()),
            'callout_content_html' => $this->resource->translatedHtml('callout_content', app()->getLocale()),
            'related_title' => $this->resource->translated('related_title', app()->getLocale()),
            'related_eyebrow' => $this->resource->translated('related_eyebrow', app()->getLocale()),
            'whatsapp_label' => $this->resource->translated('whatsapp_label', app()->getLocale()),
            'whatsapp_url' => $this->whatsapp_url,
            'title' => $this->resource->translated('title', app()->getLocale()),
            'construction_label' => $this->resource->translated('construction_label', app()->getLocale()),
            'message' => $this->resource->translated('message', app()->getLocale()),
            'message_html' => $this->resource->translatedHtml('message', app()->getLocale()),
            'cta_label' => $this->resource->translated('cta_label', app()->getLocale()),
            'cta_destination' => $this->cta_destination,
            'linkedin_destination' => $this->linkedin_destination,
            'under_construction' => $this->under_construction,
        ];
    }
}
