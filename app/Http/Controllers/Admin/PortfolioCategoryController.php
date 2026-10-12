<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmDeleteRequest;
use App\Http\Requests\Admin\SavePortfolioCategoryRequest;
use App\Models\PortfolioCategory;
use App\Services\SaveContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortfolioCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.portfolio-categories', ['categories' => PortfolioCategory::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return $this->edit(new PortfolioCategory(['sort_order' => 0]));
    }

    public function store(SavePortfolioCategoryRequest $request, SaveContent $save): RedirectResponse
    {
        $category = $save->handle(new PortfolioCategory, $request->validated());

        return redirect()->route('admin.portfolio.categories.edit', $category)->with('status', 'El contingut s’ha creat.');
    }

    public function edit(PortfolioCategory $category): View
    {
        return view('admin.portfolio-category-form', ['category' => $category]);
    }

    public function update(SavePortfolioCategoryRequest $request, PortfolioCategory $category, SaveContent $save): RedirectResponse
    {
        $save->handle($category, $request->validated());

        return redirect()->route('admin.portfolio.categories.edit', $category)->with('status', 'El contingut s’ha desat.');
    }

    public function destroy(ConfirmDeleteRequest $request, PortfolioCategory $category, SaveContent $save): RedirectResponse
    {
        $save->delete($category);

        return redirect()->route('admin.portfolio.categories.index')->with('status', 'El contingut s’ha eliminat.');
    }
}
