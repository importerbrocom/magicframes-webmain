<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SiteData;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    /** Global data every page needs: branding, header, menus, footer, contact & social links. */
    public function show(): JsonResponse
    {
        return response()->json(['data' => SiteData::site()]);
    }
}
