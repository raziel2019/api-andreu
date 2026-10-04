<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactServiceOptionOrderRequest;
use App\Models\ContactServiceOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ContactServiceOptionOrderController extends Controller
{
    public function update(UpdateContactServiceOptionOrderRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('items') as $item) {
                ContactServiceOption::query()->lockForUpdate()->findOrFail($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return redirect()->route('admin.contact.edit')->with('status', 'L’ordre s’ha desat.');
    }
}
