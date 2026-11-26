<?php

namespace App\Services;

/**
 * واجهة الإرسال. التنفيذ الحقيقي لـ FCM يُضاف لاحقاً (يحتاج حزمة firebase).
 */
interface PushGateway
{
    public function send(string $deviceToken, string $title, string $body): void;
}
