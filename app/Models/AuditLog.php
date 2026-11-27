<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['actor_admin_id', 'action', 'subject_type', 'subject_id', 'note', 'created_at'])]
class AuditLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** اسم الإجراء بالعربي للوحة الإدارة. */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'change_request.approved' => 'موافقة على تعديل',
            'change_request.rejected' => 'رفض طلب تعديل',
            'ownership_claim.approved' => 'موافقة على ملكية',
            'ownership_claim.rejected' => 'رفض طلب ملكية',
            'reviewer.created' => 'إضافة مراجع',
            'reviewer.suspended' => 'إيقاف مراجع',
            'vehicle.updated' => 'تعديل سيارة',
            'vehicle.archived' => 'أرشفة سيارة',
            'vehicle.restored' => 'استرجاع سيارة',
            'vehicle.deleted' => 'حذف سيارة نهائياً',
            default => $this->action,
        };
    }

    /** وصف الجهة المتأثرة بدون اسم الكلاس الداخلي. */
    public function subjectLabel(): string
    {
        $type = match ($this->subject_type) {
            ChangeRequest::class => 'طلب تعديل',
            OwnershipClaim::class => 'طلب ملكية',
            AdminUser::class => 'مراجع',
            default => 'عنصر',
        };

        return $type.' #'.$this->subject_id;
    }
}
