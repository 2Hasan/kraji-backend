<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;

class ServiceTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ServiceType::orderBy('id')->get());
    }
}
