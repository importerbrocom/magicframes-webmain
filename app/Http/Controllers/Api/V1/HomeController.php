<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SiteData;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => SiteData::home()]);
    }
}
