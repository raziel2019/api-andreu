<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\InterfaceMediaSlots;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => SiteSetting::findOrFail(1)]);
    }

    public function update(UpdateSiteSettingRequest $request, SaveContent $save): RedirectResponse
    {
        $images = ['logo' => 'logo_path'];
        foreach (array_keys(InterfaceMediaSlots::LABELS) as $key) {
            $images['interface_'.$key] = 'interface_media->'.$key;
        }
        $save->handle(SiteSetting::findOrFail(1), $request->validated(), $images);

        return redirect()->route('admin.settings.edit')->with('status', 'La configuració s’ha desat.');
    }
}
