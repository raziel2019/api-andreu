<?php

namespace App\Services;

use App\Models\ServiceFeature;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class SyncContentItems
{
    public function __construct(private CmsMedia $media) {}

    /** @param list<array<string, mixed>> $items */
    public function handle(HasMany $relation, array $items): void
    {
        $retained = [];
        foreach ($items as $item) {
            $record = isset($item['id']) ? (clone $relation)->whereKey($item['id'])->firstOrFail() : $relation->make();
            if ($record instanceof ServiceFeature && ! array_key_exists('icon_path', $item)) {
                $item['icon_path'] = $this->media->assignment($item, 'icon', $record->icon_path);
            }
            $record->fill(Arr::except($item, ['id', 'icon', 'icon_asset_id', 'remove_icon']))->save();
            $retained[] = $record->getKey();
        }
        $relation->whereNotIn('id', $retained)->delete();
    }
}
