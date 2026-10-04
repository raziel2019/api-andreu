<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SaveContent
{
    public function __construct(private CmsMedia $media, private SyncContentItems $items) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $images  Upload input => model path field
     * @param  array<string, string>  $collections  Validated input => relationship method
     */
    public function handle(Model $record, array $data, array $images = [], array $collections = []): Model
    {
        $newPaths = [];
        $oldPaths = [];
        try {
            foreach ($images as $input => $column) {
                if (($data[$input] ?? null) instanceof UploadedFile) {
                    $newPaths[$column] = $this->media->store($data[$input], $input);
                }
            }
            $record = DB::transaction(function () use ($record, $data, $images, $collections, $newPaths, &$oldPaths): Model {
                if ($record->exists) {
                    $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
                }
                $excluded = [...array_keys($images), ...array_keys($collections)];
                foreach ($images as $input => $column) {
                    $excluded[] = 'remove_'.$input;
                }
                $attributes = Arr::except($data, $excluded);
                foreach ($images as $input => $column) {
                    if (isset($newPaths[$column]) || ($data['remove_'.$input] ?? false)) {
                        $oldPaths[] = $record->getAttribute($column);
                        $attributes[$column] = $newPaths[$column] ?? null;
                    }
                }
                $record->fill($attributes)->save();
                foreach ($collections as $input => $method) {
                    $this->items->handle($record->$method(), $data[$input]);
                }

                return $record;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($newPaths));
            throw $exception;
        }
        foreach (array_unique(array_filter($oldPaths)) as $path) {
            $this->media->deleteIfUnused($path);
        }

        return $record;
    }

    /** @param list<string> $pathFields */
    public function delete(Model $record, array $pathFields = []): void
    {
        $paths = DB::transaction(function () use ($record, $pathFields): array {
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
            $paths = array_values($record->only($pathFields));
            $record->delete();

            return $paths;
        });
        foreach ($paths as $path) {
            $this->media->deleteIfUnused($path);
        }
    }
}
