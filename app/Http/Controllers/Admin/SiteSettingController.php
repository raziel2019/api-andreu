<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
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
        $save->handle(SiteSetting::findOrFail(1), $request->validated(), ['logo' => 'logo_path']);

        return redirect()->route('admin.settings.edit')->with('status', 'La configuració s’ha desat.');
    }
}
