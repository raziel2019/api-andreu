<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHomePageRequest;
use App\Models\HomePage;
use App\Services\HomeSummaryIcons;
use App\Services\UpdateHomePage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomePageController extends Controller
{
    public function edit(): View
    {
        return view('admin.home', [
            'home' => HomePage::with('serviceSummaries')->findOrFail(1),
            'summaryIcons' => array_map(fn (array $option): array => [
                ...$option,
                'preview_url' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(database_path('seeders/media/'.basename($option['path'])))),
            ], HomeSummaryIcons::OPTIONS),
        ]);
    }

    public function update(UpdateHomePageRequest $request, UpdateHomePage $update): RedirectResponse
    {
        $update->handle($request->validated());

        return redirect()->route('admin.home.edit')->with('status', 'La pàgina d’inici s’ha desat.');
    }
}
