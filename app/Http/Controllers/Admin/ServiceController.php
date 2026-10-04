<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmDeleteRequest;
use App\Http\Requests\Admin\SaveServiceRequest;
use App\Models\Service;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services', ['services' => Service::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.service-form', ['service' => new Service(['active' => true, 'sort_order' => 0])]);
    }

    public function store(SaveServiceRequest $request, SaveContent $save): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::substr(Str::slug($data['title']['ca']) ?: 'servei', 0, 80).'-'.Str::lower(Str::random(8));
        $service = $save->handle(new Service, $data, ['icon' => 'icon_path'], ['features' => 'features']);

        return redirect()->route('admin.services.edit', $service)->with('status', 'El servei s’ha creat.');
    }

    public function edit(Service $service): View
    {
        return view('admin.service-form', ['service' => $service->load('features')]);
    }

    public function update(SaveServiceRequest $request, Service $service, SaveContent $save): RedirectResponse
    {
        $save->handle($service, $request->validated(), ['icon' => 'icon_path'], ['features' => 'features']);

        return redirect()->route('admin.services.edit', $service)->with('status', 'El servei s’ha desat.');
    }

    public function destroy(ConfirmDeleteRequest $request, Service $service, SaveContent $save): RedirectResponse
    {
        $save->delete($service, ['icon_path']);

        return redirect()->route('admin.services.index')->with('status', 'El servei s’ha eliminat.');
    }
}
