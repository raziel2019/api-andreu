<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmDeleteRequest;
use App\Http\Requests\Admin\SaveContactServiceOptionRequest;
use App\Models\ContactPage;
use App\Models\ContactServiceOption;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactServiceOptionController extends Controller
{
    public function create(): View
    {
        return view('admin.contact-option-form', ['option' => new ContactServiceOption(['active' => true, 'sort_order' => 0])]);
    }

    public function store(SaveContactServiceOptionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = 'opcio-'.Str::lower(Str::random(12));
        $option = ContactPage::findOrFail(1)->serviceOptions()->create($data);

        return redirect()->route('admin.contact.options.edit', $option)->with('status', 'L’opció s’ha creat.');
    }

    public function edit(ContactServiceOption $option): View
    {
        abort_unless($option->contact_page_id === 1, 404);

        return view('admin.contact-option-form', compact('option'));
    }

    public function update(SaveContactServiceOptionRequest $request, ContactServiceOption $option): RedirectResponse
    {
        abort_unless($option->contact_page_id === 1, 404);
        $option->update($request->validated());

        return redirect()->route('admin.contact.options.edit', $option)->with('status', 'L’opció s’ha desat.');
    }

    public function destroy(ConfirmDeleteRequest $request, ContactServiceOption $option, SaveContent $save): RedirectResponse
    {
        abort_unless($option->contact_page_id === 1, 404);
        $save->delete($option);

        return redirect()->route('admin.contact.edit')->with('status', 'L’opció s’ha eliminat.');
    }
}
