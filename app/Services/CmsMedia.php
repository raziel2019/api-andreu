<?php

namespace App\Services;

use App\Models\AboutPage;
use App\Models\HomePage;
use App\Models\HomeServiceSummary;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class CmsMedia
{
    public function store(UploadedFile $file, string $field): string
    {
        try {
            $path = $file->store('cms', 'public');
        } catch (Throwable $exception) {
            report($exception);
            $path = false;
        }
        if ($path === false) {
            throw ValidationException::withMessages([$field => 'No s’ha pogut desar la imatge. Torna-ho a provar.']);
        }

        return $path;
    }

    public function deleteIfUnused(?string $path): void
    {
        if (! $path) {
            return;
        }
        $referenced = HomePage::where('hero_image_path', $path)->orWhere('portfolio_image_path', $path)->exists()
            || AboutPage::where('image_path', $path)->exists()
            || ServicePage::where('image_path', $path)->exists()
            || SiteSetting::where('logo_path', $path)->exists()
            || Service::where('icon_path', $path)->exists()
            || HomeServiceSummary::where('icon_path', $path)->exists();
        if (! $referenced) {
            Storage::disk('public')->delete($path);
        }
    }
}
