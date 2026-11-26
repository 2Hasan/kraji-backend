<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['service_record_id', 'requested_by_user_id', 'type', 'payload_json', 'original_json', 'reason', 'status', 'reviewed_by_admin_id', 'review_note', 'reviewed_at'])]
class ChangeRequest extends Model
{
    protected function casts(): array
    {
        return ['payload_json' => 'array', 'original_json' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function serviceRecord()
    {
        return $this->belongsTo(ServiceRecord::class);
    }
}
