<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/** تنفيذ مؤقت: يكتب الإشعار في السجل بدل ما يرسله لـ FCM. */
class LogPushGateway implements PushGateway
{
    public function send(string $deviceToken, string $title, string $body): void
    {
        Log::info('push (log gateway)', ['title' => $title, 'body' => $body]);
    }
}
