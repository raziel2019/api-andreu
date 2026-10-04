<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioPageResource;
use App\Models\PortfolioPage;

class PortfolioPageController extends Controller
{
    public function show(): PortfolioPageResource
    {
        return new PortfolioPageResource(PortfolioPage::query()->findOrFail(1));
    }
}
