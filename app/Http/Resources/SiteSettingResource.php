<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SiteSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pages' => $this->pages,
            'location' => $this->resource->translated('location', app()->getLocale()),
            'callout_title' => $this->resource->translated('callout_title', app()->getLocale()),
            'callout_content' => $this->resource->translated('callout_content', app()->getLocale()),
            'callout_label' => $this->resource->translated('callout_label', app()->getLocale()),
            'footer_description' => $this->resource->translated('footer_description', app()->getLocale()),
            'footer_cta_label' => $this->resource->translated('footer_cta_label', app()->getLocale()),
            'footer_copyright' => $this->resource->translated('footer_copyright', app()->getLocale()),
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'linkedin_url' => $this->linkedin_url,
            'instagram_url' => $this->instagram_url,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'callout_destination' => $this->callout_destination,
            'footer_cta_destination' => $this->footer_cta_destination,
        ];
    }
}
