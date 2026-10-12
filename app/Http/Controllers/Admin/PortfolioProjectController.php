<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmDeleteRequest;
use App\Http\Requests\Admin\SavePortfolioProjectRequest;
use App\Models\MediaAsset;
use App\Models\PortfolioCategory;
use App\Models\PortfolioProject;
use App\Services\SaveContent;
use App\Services\SavePortfolioProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PortfolioProjectController extends Controller
{
    public function index(): View
    {
        return view('admin.portfolio-projects', ['projects' => PortfolioProject::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return $this->edit(new PortfolioProject(['status' => 'draft', 'sort_order' => 0, 'gallery' => []]));
    }

    public function store(SavePortfolioProjectRequest $request, SavePortfolioProject $save): RedirectResponse
    {
        $project = $save->handle(new PortfolioProject, $request->validated());

        return redirect()->route('admin.portfolio.projects.edit', $project)->with('status', 'El contingut s’ha creat.');
    }

    public function edit(PortfolioProject $project): View
    {
        return view('admin.portfolio-project-form', ['project' => $project, 'categories' => PortfolioCategory::orderBy('sort_order')->orderBy('id')->get(), 'galleryAssets' => MediaAsset::where('disk', 'public')->pluck('id', 'path')]);
    }

    public function update(SavePortfolioProjectRequest $request, PortfolioProject $project, SavePortfolioProject $save): RedirectResponse
    {
        $save->handle($project, $request->validated());

        return redirect()->route('admin.portfolio.projects.edit', $project)->with('status', 'El contingut s’ha desat.');
    }

    public function destroy(ConfirmDeleteRequest $request, PortfolioProject $project, SaveContent $save): RedirectResponse
    {
        $save->delete($project, ['featured_image_path']);

        return redirect()->route('admin.portfolio.projects.index')->with('status', 'El contingut s’ha eliminat.');
    }
}
