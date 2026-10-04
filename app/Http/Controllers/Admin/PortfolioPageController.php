<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePortfolioPageRequest;
use App\Models\PortfolioPage;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortfolioPageController extends Controller
{
    public function edit(): View
    {
        return view('admin.portfolio', ['page' => PortfolioPage::query()->findOrFail(1)]);
    }

    public function update(UpdatePortfolioPageRequest $request, SaveContent $save): RedirectResponse
    {
        $save->handle(PortfolioPage::findOrFail(1), $request->validated(), [], []);

        return redirect()->route('admin.portfolio.edit')->with('status', 'El contingut s’ha desat.');
    }
}
