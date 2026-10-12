<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioProjectResource;
use App\Models\PortfolioProject;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortfolioProjectPreviewController extends Controller
{
    public function show(Request $request, PortfolioProject $project): Response
    {
        $project->load('category');

        return response()->view('admin.portfolio-project-preview', [
            'project' => $project,
            'content' => (new PortfolioProjectResource($project))->withDetails()->resolve($request),
            'locale' => app()->getLocale(),
        ])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
