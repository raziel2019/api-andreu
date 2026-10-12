<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaCatalog
{
    public function __construct(private RasterImages $rasters) {}

    public function register(string $path, ?string $filename = null, ?array $alt = null): ?MediaAsset
    {
        if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, ':') || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        if (Storage::disk('public')->size($path) > 5120 * 1024) {
            return null;
        }
        $bytes = Storage::disk('public')->get($path);
        $image = @getimagesizefromstring($bytes);
        $trustedSvg = str_starts_with($path, 'seed/') && str_ends_with($path, '.svg')
            && is_file(database_path('seeders/media/'.basename($path)))
            && hash_equals(hash_file('sha256', database_path('seeders/media/'.basename($path))), hash('sha256', $bytes));
        if (! $trustedSvg && (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp'], true) || ! $image || $image[0] > 6000 || $image[1] > 6000 || $image[0] * $image[1] > 16000000 || ! in_array($image['mime'], ['image/png', 'image/jpeg', 'image/webp'], true))) {
            return null;
        }

        if (! $trustedSvg) {
            try {
                $this->rasters->encode($bytes, $image['mime']);
            } catch (\Throwable $exception) {
                return null;
            }
        }

        return MediaAsset::firstOrCreate(['disk' => 'public', 'path' => $path], [
            'original_filename' => mb_substr(basename($filename ?? $path), 0, 255),
            'mime_type' => $trustedSvg ? 'image/svg+xml' : $image['mime'],
            'size' => strlen($bytes), 'width' => $image[0] ?? null, 'height' => $image[1] ?? null, 'alt' => $alt,
        ]);
    }

    public function selectedPath(int $id, string $field): string
    {
        $asset = MediaAsset::find($id);
        if (! $asset || $asset->disk !== 'public' || ! $this->register($asset->path)) {
            throw ValidationException::withMessages([$field => 'El fitxer seleccionat no està disponible.']);
        }

        return $asset->path;
    }

    public function import(): int
    {
        $paths = Storage::disk('public')->allFiles();
        foreach ((new CmsMediaSlots)->all() as $slot) {
            $path = CmsMediaSlots::path($slot);
            if ($path) {
                $paths[] = $path;
            }
        }
        $count = 0;
        foreach (array_unique($paths) as $path) {
            if ($this->register($path)) {
                $count++;
            }
        }

        return $count;
    }
}
