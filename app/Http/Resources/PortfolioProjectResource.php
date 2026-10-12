<?php

namespace App\Http\Resources;

use App\Services\CmsMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioProjectResource extends JsonResource
{
    private bool $includeDetails = false;

    public function withDetails(): static
    {
        $this->includeDetails = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->resource->translated('title', $locale),
            'description' => $this->resource->translated('description', $locale),
            'description_html' => $this->resource->translatedHtml('description', $locale),
            'category' => new PortfolioCategoryResource($this->whenLoaded('category')),
            'featured_image_url' => CmsMedia::publicUrl($this->featured_image_path),
            'featured_image_alt' => $this->resource->translated('featured_image_alt', $locale),
            'year' => $this->year,
            'client' => $this->client,
            'sort_order' => $this->sort_order,
            'body' => $this->when($this->includeDetails, fn (): string => $this->resource->translatedHtml('body', $locale) ?? ''),
            'gallery' => $this->when($this->includeDetails, function () use ($locale): array {
                $gallery = $this->gallery ?? [];
                usort($gallery, fn (array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']);

                return array_map(fn (array $image): array => [
                    'url' => CmsMedia::publicUrl($image['path']),
                    'alt' => $image['alt'][$locale] ?? $image['alt']['ca'] ?? null,
                    'caption' => $image['caption'][$locale] ?? $image['caption']['ca'] ?? null,
                    'sort_order' => $image['sort_order'],
                ], $gallery);
            }),
        ];
    }
}
