<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * يخدم الملف فقط عبر رابط موقّع. الرابط بعد انتهاء صلاحيته يرجع 403 من middleware الـ signed.
     */
    public function show(Request $request, ServiceRecordAttachment $attachment): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        return Storage::disk('local')->response($attachment->storage_key);
    }
}
