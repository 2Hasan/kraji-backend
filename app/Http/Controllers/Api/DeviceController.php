<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'in:ios,android'],
        ]);

        $device = Device::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'], 'last_seen_at' => Carbon::now()],
        );

        return response()->json($device, 201);
    }
}
