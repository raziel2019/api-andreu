<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class SyncContentItems
{
    /** @param list<array<string, mixed>> $items */
    public function handle(HasMany $relation, array $items): void
    {
        $retained = [];
        foreach ($items as $item) {
            $record = isset($item['id']) ? (clone $relation)->whereKey($item['id'])->firstOrFail() : $relation->make();
            $record->fill(Arr::except($item, ['id']))->save();
            $retained[] = $record->getKey();
        }
        $relation->whereNotIn('id', $retained)->delete();
    }
}
