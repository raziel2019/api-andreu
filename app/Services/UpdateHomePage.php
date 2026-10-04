<?php

namespace App\Services;

use App\Models\HomePage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateHomePage
{
    public function __construct(private CmsMedia $media) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): void
    {
        $newPaths = [];
        $oldPaths = [];
        try {
            foreach (['hero', 'portfolio'] as $image) {
                if (($data[$image.'_image'] ?? null) instanceof UploadedFile) {
                    $path = $data[$image.'_image']->store('home', 'public');
                    if ($path === false) {
                        throw new RuntimeException('No s’ha pogut desar la imatge.');
                    }
                    $newPaths[$image.'_image_path'] = $path;
                }
            }

            DB::transaction(function () use ($data, $newPaths, &$oldPaths): void {
                $home = HomePage::query()->lockForUpdate()->findOrFail(1);
                $attributes = Arr::except($data, ['service_summaries', 'hero_image', 'portfolio_image', 'remove_hero_image', 'remove_portfolio_image']);
                foreach (['hero', 'portfolio'] as $image) {
                    $field = $image.'_image_path';
                    if (isset($newPaths[$field]) || ($data['remove_'.$image.'_image'] ?? false)) {
                        $oldPaths[] = $home->$field;
                        $attributes[$field] = $newPaths[$field] ?? null;
                    }
                }
                $home->update($attributes);
                $summaries = $data['service_summaries'];
                $home->serviceSummaries()->whereNotIn('slug', array_column($summaries, 'slug'))->delete();
                foreach ($summaries as $summary) {
                    $home->serviceSummaries()->updateOrCreate(['slug' => $summary['slug']], $summary);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));
            throw $exception;
        }

        foreach (array_unique(array_filter($oldPaths)) as $oldPath) {
            $this->media->deleteIfUnused($oldPath);
        }
    }
}
