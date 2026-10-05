<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutPageResource;
use App\Models\AboutPage;

class AboutPageController extends Controller
{
    public function show(): AboutPageResource
    {
        return new AboutPageResource(AboutPage::query()->where('active', true)->with(['benefits', 'workingMethodSteps'])->findOrFail(1));
    }
}
