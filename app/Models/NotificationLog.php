<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'vehicle_id', 'type', 'sent_at'])]
class NotificationLog extends Model
{
    protected $table = 'notification_log';

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
