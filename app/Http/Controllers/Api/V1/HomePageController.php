<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomePageResource;
use App\Models\HomePage;

class HomePageController extends Controller
{
    public function show(): HomePageResource
    {
        return new HomePageResource(HomePage::query()->with(['serviceSummaries'])->findOrFail(1));
    }
}
