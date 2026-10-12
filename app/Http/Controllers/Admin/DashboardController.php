<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\PageVisibility;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(PageVisibility $visibility): View
    {
        return view('admin.dashboard', [
            'serviceCount' => Service::count(),
            'pages' => $visibility->publicMap(),
            'languageCount' => count(['ca', 'es']),
        ]);
    }

    public function content(PageVisibility $visibility): View
    {
        return view('admin.content', ['pages' => $visibility->publicMap()]);
    }
}
