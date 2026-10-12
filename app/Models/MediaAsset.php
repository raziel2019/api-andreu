<?php

namespace App\Models;

use Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['disk', 'path', 'original_filename', 'mime_type', 'size', 'width', 'height', 'alt'])]
class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['alt' => 'array', 'size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
