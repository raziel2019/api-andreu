<?php

namespace App\Services;

use App\Models\PortfolioProject;
use Illuminate\Http\UploadedFile;
use Throwable;

class SavePortfolioProject
{
    public function __construct(private SaveContent $save, private CmsMedia $media) {}

    public function handle(PortfolioProject $project, array $data): PortfolioProject
    {
        $newPaths = [];
        try {
            if (array_key_exists('gallery', $data)) {
                $gallery = [];
                foreach ($data['gallery'] as $item) {
                    if ($item['remove_image'] ?? false) {
                        continue;
                    }
                    $path = $this->media->assignment($item, 'image', null);
                    if (($item['image'] ?? null) instanceof UploadedFile) {
                        $newPaths[] = $path;
                    }
                    $gallery[] = ['path' => $path, 'alt' => $item['alt'] ?? [], 'caption' => $item['caption'] ?? [], 'sort_order' => (int) $item['sort_order']];
                }
                usort($gallery, fn (array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']);
                $data['gallery'] = $gallery;
            }
            $this->save->handle($project, $data, ['featured_image' => 'featured_image_path']);

            return $project->fresh();
        } catch (Throwable $exception) {
            $this->media->discard($newPaths);
            throw $exception;
        }
    }
}
