<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;

class SiteSettingController extends Controller
{
    public function show(): SiteSettingResource
    {
        return new SiteSettingResource(SiteSetting::query()->findOrFail(1));
    }
}
