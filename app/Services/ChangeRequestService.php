<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\ChangeRequest;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeRequestService
{
    /** الحقول اللي يقدر التعديل يمسّها. */
    private const EDITABLE = ['service_date', 'odometer', 'cost', 'workshop_name', 'notes'];

    public function submit(ServiceRecord $record, User $by, string $type, ?array $payload, string $reason): ChangeRequest
    {
        return ChangeRequest::create([
            'service_record_id' => $record->id,
            'requested_by_user_id' => $by->id,
            'type' => $type,
            'payload_json' => $type === 'edit' ? array_intersect_key($payload ?? [], array_flip(self::EDITABLE)) : null,
            'original_json' => $record->only(array_merge(self::EDITABLE, ['status'])),
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function approve(ChangeRequest $req, AdminUser $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($req, $reviewer, $note) {
            $record = $req->serviceRecord;

            if ($req->type === 'delete') {
                // الحذف: السجل يبقى ويُخفى بحالة "محذوف بموافقة"
                $record->update([
                    'status' => 'voided',
                    'void_reason' => $req->reason,
                    'voided_at' => now(),
                ]);
            } else {
                $record->update($req->payload_json ?? []);
            }

            $req->update([
                'status' => 'approved',
                'reviewed_by_admin_id' => $reviewer->id,
                'review_note' => $note,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function reject(ChangeRequest $req, AdminUser $reviewer, string $note): void
    {
        $req->update([
            'status' => 'rejected',
            'reviewed_by_admin_id' => $reviewer->id,
            'review_note' => $note,
            'reviewed_at' => now(),
        ]);
    }
}
