<?php

namespace App\Services;

use App\Models\Device;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class NotificationSender
{
    public function __construct(private PushGateway $push)
    {
    }

    /** الأنواع المفعّلة افتراضياً، والمستخدم يقدر يطفيها كل نوع لحاله. */
    public function isEnabled(User $user, string $type): bool
    {
        return ($user->notification_settings[$type] ?? true) === true;
    }

    public function send(User $user, string $type, array $data, ?int $vehicleId = null): void
    {
        if (! $this->isEnabled($user, $type)) {
            return;
        }

        Device::where('user_id', $user->id)->each(function (Device $device) use ($data) {
            $this->push->send($device->fcm_token, $data['title'] ?? '', $data['body'] ?? '');
        });

        NotificationLog::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicleId,
            'type' => $type,
            'sent_at' => Carbon::now(),
        ]);
    }
}
