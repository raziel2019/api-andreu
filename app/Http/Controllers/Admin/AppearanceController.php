<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppearanceRequest;
use App\Models\SiteSetting;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function edit(): View
    {
        return view('admin.appearance', ['settings' => SiteSetting::findOrFail(1)]);
    }

    public function update(UpdateAppearanceRequest $request, SaveContent $save): RedirectResponse
    {
        $save->handle(SiteSetting::findOrFail(1), $request->validated());

        return redirect()->route('admin.appearance.index')->with('status', 'Los colores se han guardado.');
    }
}
