<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\UpdateMediaSlotRequest;
use App\Models\HomeServiceSummary;
use App\Models\MediaAsset;
use App\Services\CmsMedia;
use App\Services\CmsMediaSlots;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    public function index(CmsMediaSlots $slots): View
    {
        return view('admin.media', ['slots' => $slots->all(), 'assets' => MediaAsset::orderByDesc('id')->paginate(24)]);
    }

    public function store(StoreMediaAssetRequest $request, CmsMedia $media): RedirectResponse
    {
        $path = $media->store($request->file('image'), 'image');
        MediaAsset::where('disk', 'public')->where('path', $path)->update(['alt' => $request->validated('alt')]);

        return redirect()->route('admin.media.index')->with('status', 'El fitxer s’ha afegit a la biblioteca.');
    }

    public function update(UpdateMediaSlotRequest $request, CmsMediaSlots $slots, SaveContent $save, string $slot): RedirectResponse
    {
        $target = $slots->find($slot);
        $data = $request->safe()->only(['image', 'image_asset_id', 'remove_image']);
        if ($target['record'] instanceof HomeServiceSummary) {
            $data['icon'] = null;
        }
        $save->handle($target['record'], $data, ['image' => $target['column']]);

        return redirect()->route('admin.media.index')->with('status', 'La imatge s’ha desat.');
    }
}
