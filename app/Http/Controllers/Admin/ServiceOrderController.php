<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServiceOrderRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    public function update(UpdateServiceOrderRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('items') as $item) {
                Service::query()->lockForUpdate()->findOrFail($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return redirect()->route('admin.services.index')->with('status', 'L’ordre s’ha desat.');
    }
}
