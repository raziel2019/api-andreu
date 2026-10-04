<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactPageResource;
use App\Models\ContactPage;

class ContactPageController extends Controller
{
    public function show(): ContactPageResource
    {
        return new ContactPageResource(ContactPage::query()->with(['serviceOptions' => fn ($query) => $query->where('active', true)])->findOrFail(1));
    }
}
