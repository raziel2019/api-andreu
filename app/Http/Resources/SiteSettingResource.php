<?php

namespace App\Http\Resources;

use App\Services\CmsMedia;
use App\Services\InterfaceMediaSlots;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pages' => $this->pages,
            'theme' => [
                'primary_color' => $this->primary_color,
                'background_color' => $this->background_color,
            ],
            'location' => $this->resource->translated('location', app()->getLocale()),
            'callout_title' => $this->resource->translated('callout_title', app()->getLocale()),
            'callout_content' => $this->resource->translated('callout_content', app()->getLocale()),
            'callout_content_html' => $this->resource->translatedHtml('callout_content', app()->getLocale()),
            'callout_label' => $this->resource->translated('callout_label', app()->getLocale()),
            'footer_description' => $this->resource->translated('footer_description', app()->getLocale()),
            'footer_description_html' => $this->resource->translatedHtml('footer_description', app()->getLocale()),
            'footer_cta_label' => $this->resource->translated('footer_cta_label', app()->getLocale()),
            'footer_copyright' => $this->resource->translated('footer_copyright', app()->getLocale()),
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'whatsapp_number' => $this->whatsapp_number,
            'whatsapp_url' => $this->resource->whatsappUrl(),
            'linkedin_url' => $this->linkedin_url,
            'instagram_url' => $this->instagram_url,
            'interface_media' => array_map(fn (?string $path): ?string => CmsMedia::publicUrl($path), array_replace(array_fill_keys(array_keys(InterfaceMediaSlots::LABELS), null), $this->interface_media ?? [])),
            'logo_url' => CmsMedia::publicUrl($this->logo_path),
            'callout_destination' => $this->callout_destination,
            'footer_cta_destination' => $this->footer_cta_destination,
        ];
    }
}
