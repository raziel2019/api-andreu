<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLegalPageRequest;
use App\Models\LegalPage;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function edit(string $section): View
    {
        return view('admin.legal', ['page' => LegalPage::where('type', LegalPage::TYPES[$section])->firstOrFail(), 'section' => $section]);
    }

    public function update(UpdateLegalPageRequest $request, SaveContent $save, string $section): RedirectResponse
    {
        $save->handle(LegalPage::where('type', LegalPage::TYPES[$section])->firstOrFail(), $request->validated());

        return redirect()->route('admin.legal.edit', $section)->with('status', 'El contingut s’ha desat.');
    }
}
