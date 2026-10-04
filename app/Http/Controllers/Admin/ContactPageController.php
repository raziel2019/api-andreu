<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactPageRequest;
use App\Models\ContactPage;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactPageController extends Controller
{
    public function edit(): View
    {
        return view('admin.contact', ['page' => ContactPage::query()->with('serviceOptions')->findOrFail(1)]);
    }

    public function update(UpdateContactPageRequest $request, SaveContent $save): RedirectResponse
    {
        $save->handle(ContactPage::findOrFail(1), $request->validated(), [], []);

        return redirect()->route('admin.contact.edit')->with('status', 'El contingut s’ha desat.');
    }
}
