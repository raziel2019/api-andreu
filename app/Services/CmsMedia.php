<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CmsMedia
{
    public function __construct(private MediaCatalog $catalog, private RasterImages $rasters) {}

    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (filter_var($path, FILTER_VALIDATE_URL) && in_array(parse_url($path, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return $path;
        }
        if (str_starts_with($path, '/storage/') && ! str_contains($path, '..')) {
            return rtrim((string) config('app.url'), '/').$path;
        }
        if (str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, ':')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function store(UploadedFile $file, string $field): string
    {
        $bytes = file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($bytes);
        $formats = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (! $info || ! isset($formats[$info['mime']]) || ! in_array($extension, $info['mime'] === 'image/jpeg' ? ['jpg', 'jpeg'] : [$formats[$info['mime']]], true)
            || $file->getSize() > 5120 * 1024 || $info[0] > 6000 || $info[1] > 6000 || $info[0] * $info[1] > 16000000) {
            throw ValidationException::withMessages([$field => 'Cal una imatge PNG, JPEG o WebP vàlida (màxim 5 MB, 6000 píxels i 16 megapíxels).']);
        }
        try {
            $cleanBytes = $this->rasters->encode($bytes, $info['mime']);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([$field => 'No s’ha pogut llegir la imatge.']);
        }
        $path = 'cms/'.Str::uuid().'.'.$formats[$info['mime']];
        try {
            if (! Storage::disk('public')->put($path, $cleanBytes)) {
                throw new \RuntimeException('Image storage failed.');
            }
            if (! $this->catalog->register($path, $file->getClientOriginalName())) {
                throw new \RuntimeException('Image registration failed.');
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            report($exception);
            throw ValidationException::withMessages([$field => 'No s’ha pogut desar la imatge. Torna-ho a provar.']);
        }

        return $path;
    }

    /** Registered assets remain reusable after unlinking or deleting CMS content. */
    public function retain(?string $path): void
    {
        if ($path) {
            $this->catalog->register($path);
        }
    }

    /** Only newly created files belonging to a failed save may be discarded. */
    public function discard(array $paths): void
    {
        foreach ($paths as $path) {
            MediaAsset::where('disk', 'public')->where('path', $path)->delete();
            Storage::disk('public')->delete($path);
        }
    }

    /** @param array<string, mixed> $data */
    public function assignment(array $data, string $input, ?string $current): ?string
    {
        if (($data[$input] ?? null) instanceof UploadedFile) {
            return $this->store($data[$input], $input);
        }
        if ($data['remove_'.$input] ?? false) {
            return null;
        }
        if ($data[$input.'_asset_id'] ?? null) {
            return $this->catalog->selectedPath((int) $data[$input.'_asset_id'], $input.'_asset_id');
        }

        return $current;
    }
}
