<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAboutPageRequest;
use App\Models\AboutPage;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AboutPageController extends Controller
{
    public function edit(): View
    {
        return view('admin.about', ['page' => AboutPage::query()->with(['benefits', 'workingMethodSteps'])->findOrFail(1)]);
    }

    public function update(UpdateAboutPageRequest $request, SaveContent $save): RedirectResponse
    {
        $save->handle(AboutPage::findOrFail(1), $request->validated(), ['image' => 'image_path'], ['benefits' => 'benefits', 'working_method_steps' => 'workingMethodSteps']);

        return redirect()->route('admin.about.edit')->with('status', 'El contingut s’ha desat.');
    }
}
