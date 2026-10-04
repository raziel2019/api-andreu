<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use App\Models\ContactPage;
use App\Models\PortfolioPage;
use App\Models\Service;
use App\Models\ServicePage;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function show(string $section): View
    {
        [$title, $records] = match ($section) {
            'about' => ['Sobre mi', collect([AboutPage::with(['benefits', 'workingMethodSteps'])->findOrFail(1)])],
            'services' => ['Serveis', collect([ServicePage::findOrFail(1)])->concat(Service::with('features')->orderBy('sort_order')->orderBy('id')->get())],
            'portfolio' => ['Portafoli', collect([PortfolioPage::findOrFail(1)])],
            'contact' => ['Contacte', collect([ContactPage::with('serviceOptions')->findOrFail(1)])],
        };

        return view('admin.section', compact('title', 'records'));
    }
}
