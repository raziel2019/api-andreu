<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServicePageResource;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\ServicePage;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ServiceResource::collection(Service::with('features')->where('active', true)->orderBy('sort_order')->orderBy('id')->get()),
            'meta' => ['locale' => app()->getLocale(), 'page' => new ServicePageResource(ServicePage::findOrFail(1))],
        ]);
    }
}
