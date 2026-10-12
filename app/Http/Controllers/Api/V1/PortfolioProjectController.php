<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioCategoryResource;
use App\Http\Resources\PortfolioPageResource;
use App\Http\Resources\PortfolioProjectResource;
use App\Models\PortfolioCategory;
use App\Models\PortfolioPage;
use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioProjectController extends Controller
{
    private function page(): PortfolioPage
    {
        return PortfolioPage::where('active', true)->where('under_construction', false)->findOrFail(1);
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->page();
        $data = $request->validate(['category' => ['nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $projects = PortfolioProject::with('category')->published()->ordered()
            ->when($data['category'] ?? null, fn (Builder $query, string $slug): Builder => $query->whereHas('category', fn (Builder $category): Builder => $category->where('slug', $slug)))
            ->paginate(24)->withQueryString();

        return response()->json([
            'data' => PortfolioProjectResource::collection($projects),
            'meta' => ['locale' => app()->getLocale(), 'page' => new PortfolioPageResource($page), 'categories' => PortfolioCategoryResource::collection(PortfolioCategory::whereHas('projects', fn (Builder $query): Builder => $query->published())->orderBy('sort_order')->orderBy('id')->get()), 'current_page' => $projects->currentPage(), 'last_page' => $projects->lastPage(), 'total' => $projects->total()],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = $this->page();
        $project = PortfolioProject::with('category')->published()->where('slug', $slug)->firstOrFail();
        $related = PortfolioProject::with('category')->published()->whereKeyNot($project->id)
            ->orderByRaw('CASE WHEN portfolio_category_id = ? THEN 0 ELSE 1 END', [$project->portfolio_category_id])
            ->ordered()->limit(3)->get();

        return response()->json([
            'data' => (new PortfolioProjectResource($project))->withDetails(),
            'meta' => ['locale' => app()->getLocale(), 'page' => new PortfolioPageResource($page), 'related_projects' => PortfolioProjectResource::collection($related)],
        ]);
    }
}
