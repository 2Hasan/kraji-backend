<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlateTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlateTemplateController extends Controller
{
    /** القوالب المفعّلة لنوع المركبة فقط. */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['body_type' => ['required', 'string', 'max:40']]);

        return response()->json(
            PlateTemplate::where('status', 'active')->where('body_type', $data['body_type'])->get(['id', 'code', 'name', 'body_type']),
        );
    }
}
