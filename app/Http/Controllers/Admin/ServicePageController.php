<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServicePageRequest;
use App\Models\ServicePage;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServicePageController extends Controller
{
    public function edit(): View
    {
        return view('admin.service-page', ['page' => ServicePage::query()->findOrFail(1)]);
    }

    public function update(UpdateServicePageRequest $request, SaveContent $save): RedirectResponse
    {
        $save->handle(ServicePage::findOrFail(1), $request->validated(), ['image' => 'image_path'], []);

        return redirect()->route('admin.services.page.edit')->with('status', 'El contingut s’ha desat.');
    }
}
