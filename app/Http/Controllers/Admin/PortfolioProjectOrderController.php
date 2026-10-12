<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePortfolioProjectOrderRequest;
use App\Models\PortfolioProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PortfolioProjectOrderController extends Controller
{
    public function update(UpdatePortfolioProjectOrderRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('items') as $item) {
                PortfolioProject::query()->lockForUpdate()->findOrFail($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return redirect()->route('admin.portfolio.projects.index')->with('status', 'L’ordre s’ha desat.');
    }
}
