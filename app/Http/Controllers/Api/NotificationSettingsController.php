<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    /** أنواع التنبيهات اللي يقدر المستخدم يطفيها. الافتراضي: مفعّلة. */
    public const TYPES = ['reminder_km', 'reminder_days', 'odometer_prompt', 'ownership_claims'];

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->settingsFor($request));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reminder_km' => ['sometimes', 'boolean'],
            'reminder_days' => ['sometimes', 'boolean'],
            'odometer_prompt' => ['sometimes', 'boolean'],
            'ownership_claims' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $user->update(['notification_settings' => array_merge($user->notification_settings ?? [], $data)]);

        return response()->json($this->settingsFor($request));
    }

    /** @return array<string, bool> */
    private function settingsFor(Request $request): array
    {
        $saved = $request->user()->notification_settings ?? [];

        return collect(self::TYPES)
            ->mapWithKeys(fn (string $type) => [$type => ($saved[$type] ?? true) === true])
            ->all();
    }
}
