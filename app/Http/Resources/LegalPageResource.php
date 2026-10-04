<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['type' => $this->type, 'title' => $this->resource->translated('title', app()->getLocale()), 'body' => $this->resource->translated('body', app()->getLocale())];
    }
}
