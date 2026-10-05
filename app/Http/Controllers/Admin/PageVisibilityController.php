<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageVisibilityRequest;
use App\Services\PageVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageVisibilityController extends Controller
{
    public function index(PageVisibility $visibility): View
    {
        return view('admin.page-visibility', ['labels' => PageVisibility::LABELS, 'pages' => $visibility->publicMap()]);
    }

    public function update(UpdatePageVisibilityRequest $request, PageVisibility $visibility, string $page): RedirectResponse
    {
        $visibility->page($page)->update(['active' => $request->validated('active')]);

        return redirect()->route('admin.pages.visibility.index')->with('status', 'La visibilitat s’ha desat.');
    }
}
