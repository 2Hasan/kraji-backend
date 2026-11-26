<?php

namespace App\Services;

use App\Jobs\BlurInvoiceJob;
use App\Models\ServiceRecord;
use App\Models\ServiceRecordAttachment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountDeletionService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            // مرفقات السجلات اللي أنشأها المستخدم تُعلَّم قبل ما نفصل السجل عنه
            $attachmentIds = ServiceRecordAttachment::query()
                ->whereIn('service_record_id', ServiceRecord::where('created_by_user_id', $user->id)->select('id'))
                ->pluck('id')
                ->all();

            BlurInvoiceJob::dispatchSync($attachmentIds);

            // طلبات الملكية: المعلّقة تُلغى، والباقي يفصل عن المستخدم
            DB::table('ownership_claims')
                ->where('claimant_user_id', $user->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'claimant_user_id' => null]);

            DB::table('ownership_claims')
                ->where('claimant_user_id', $user->id)
                ->update(['claimant_user_id' => null]);

            Vehicle::where('owner_user_id', $user->id)->update(['owner_user_id' => null]);
            ServiceRecord::where('created_by_user_id', $user->id)->update(['created_by_user_id' => null]);

            DB::table('devices')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();

            // البيانات الشخصية فقط: الإيميل والاسم ومعرّفات الدخول
            $user->forceFill([
                'name' => null,
                'email' => null,
                'password' => Str::random(64),
                'locale' => 'ar',
            ])->save();

            $user->delete();
        });
    }
}
