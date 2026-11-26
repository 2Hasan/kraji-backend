<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\AuditLog;
use Illuminate\Support\Carbon;

class AuditLogger
{
    public function record(AdminUser $actor, string $action, string $subjectType, int $subjectId, ?string $note = null): void
    {
        AuditLog::create([
            'actor_admin_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'note' => $note,
            'created_at' => Carbon::now(),
        ]);
    }
}
