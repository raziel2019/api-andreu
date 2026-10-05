<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;
use App\Services\PageVisibility;

class SiteSettingController extends Controller
{
    public function show(PageVisibility $visibility): SiteSettingResource
    {
        $settings = SiteSetting::query()->findOrFail(1);
        $settings->setAttribute('pages', $visibility->publicMap());

        return new SiteSettingResource($settings);
    }
}
