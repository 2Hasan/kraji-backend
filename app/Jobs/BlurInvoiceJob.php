<?php

namespace App\Jobs;

use App\Models\ServiceRecordAttachment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * يعلّم الفواتير اللي فيها بيانات المستخدم كـ"مطموسة".
 * الطمس الفعلي للصورة (تمويه الاسم ورقم الهاتف) خطوة منفصلة قبل الإطلاق،
 * وهنا نسجّل الحالة ونحفظ الملف.
 */
class BlurInvoiceJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    /** @param array<int> $attachmentIds */
    public function __construct(public array $attachmentIds)
    {
    }

    public function handle(): void
    {
        ServiceRecordAttachment::whereIn('id', $this->attachmentIds)
            ->whereNull('redacted_at')
            ->update(['redacted_at' => now()]);
    }
}
