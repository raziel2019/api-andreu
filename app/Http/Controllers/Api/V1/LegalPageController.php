<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LegalPageResource;
use App\Models\LegalPage;

class LegalPageController extends Controller
{
    public function show(string $section): LegalPageResource
    {
        return new LegalPageResource(LegalPage::where('type', LegalPage::TYPES[$section])->where('active', true)->firstOrFail());
    }
}
