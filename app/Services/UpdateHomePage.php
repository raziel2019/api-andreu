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
            foreach ($data['service_summaries'] as $summary) {
                if ($summary['icon'] ?? null) {
                    $path = HomeSummaryIcons::OPTIONS[$summary['icon']]['path'];
                    if (! Storage::disk('public')->exists($path)
                        && ! Storage::disk('public')->put($path, file_get_contents(database_path('seeders/media/'.basename($path))))) {
                        throw new RuntimeException('No s’ha pogut desar la icona.');
                    }
                }
            }

            foreach (['hero', 'portfolio'] as $image) {
                if (($data[$image.'_image'] ?? null) instanceof UploadedFile) {
                    $path = $this->media->store($data[$image.'_image'], $image.'_image');
                    $newPaths[$image.'_image_path'] = $path;
                }
            }

            foreach ($data['service_summaries'] as $index => &$summary) {
                if (($summary['custom_icon'] ?? null) instanceof UploadedFile) {
                    $path = $this->media->store($summary['custom_icon'], 'service_summaries.'.$index.'.custom_icon');
                    $newPaths['summary-'.$index] = $path;
                    $summary['custom_path'] = $path;
                }
            }
            unset($summary);

            DB::transaction(function () use ($data, $newPaths, &$oldPaths): void {
                $home = HomePage::query()->lockForUpdate()->findOrFail(1);
                $attributes = Arr::except($data, ['service_summaries', 'hero_image', 'portfolio_image', 'remove_hero_image', 'remove_portfolio_image', 'hero_image_asset_id', 'portfolio_image_asset_id']);
                foreach (['hero', 'portfolio'] as $image) {
                    $field = $image.'_image_path';
                    if (isset($newPaths[$field]) || ($data['remove_'.$image.'_image'] ?? false) || ($data[$image.'_image_asset_id'] ?? null)) {
                        $oldPaths[] = $home->$field;
                        $attributes[$field] = $newPaths[$field] ?? $this->media->assignment(Arr::except($data, [$image.'_image']), $image.'_image', $home->$field);
                    }
                }
                $home->update($attributes);
                $summaries = $data['service_summaries'];
                $home->serviceSummaries()->whereNotIn('slug', array_column($summaries, 'slug'))->delete();
                foreach ($summaries as $index => $summary) {
                    if ($summary['icon'] ?? null) {
                        $summary['icon_path'] = HomeSummaryIcons::OPTIONS[$summary['icon']]['path'];
                        $oldPaths[] = $home->serviceSummaries()->where('slug', $summary['slug'])->value('icon_path');
                    }
                    $current = $home->serviceSummaries()->where('slug', $summary['slug'])->first();
                    if (isset($summary['custom_path']) || ($summary['custom_icon_asset_id'] ?? null) || ($summary['remove_custom_icon'] ?? false)) {
                        $summary['icon_path'] = $summary['custom_path'] ?? $this->media->assignment($summary, 'custom_icon', $current?->icon_path);
                        $summary['icon'] = null;
                    }
                    $summary = Arr::except($summary, ['custom_icon', 'custom_icon_asset_id', 'remove_custom_icon', 'custom_path']);
                    $home->serviceSummaries()->updateOrCreate(['slug' => $summary['slug']], $summary);
                }
            });
        } catch (Throwable $exception) {
            $this->media->discard(array_values($newPaths));
            throw $exception;
        }

        foreach (array_unique(array_filter($oldPaths)) as $oldPath) {
            $this->media->retain($oldPath);
        }
    }
}
