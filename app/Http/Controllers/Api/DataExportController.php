<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataExportController extends Controller
{
    public function show(Request $request, DataExportService $service): JsonResponse
    {
        return response()->json($service->forUser($request->user()));
    }
}
