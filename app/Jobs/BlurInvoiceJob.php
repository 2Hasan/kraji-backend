<?php

namespace App\Jobs;

use App\Models\ServiceRecordAttachment;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * يطمس الفواتير عند حذف الحساب.
 * الصور: تمويه كامل للصورة (ما نعرف مكان الاسم والرقم، فنطمس الصورة كلها).
 * PDF: ما نقدر نموّهه هنا، فنحذف الملف ونترك السجل مع علامة الطمس.
 */
class BlurInvoiceJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    private const BLUR_PASSES = 3;

    private const PIXEL_BLOCK = 12;

    /** @param array<int> $attachmentIds */
    public function __construct(public array $attachmentIds) {}

    public function handle(): void
    {
        $disk = Storage::disk(config('filesystems.attachments_disk'));

        ServiceRecordAttachment::whereIn('id', $this->attachmentIds)
            ->whereNull('redacted_at')
            ->get()
            ->each(function (ServiceRecordAttachment $attachment) use ($disk): void {
                if ($disk->exists($attachment->storage_key)) {
                    $this->redact($disk, $attachment);
                }

                ServiceRecordAttachment::whereKey($attachment->id)->update(['redacted_at' => now()]);
            });
    }

    private function redact(Filesystem $disk, ServiceRecordAttachment $attachment): void
    {
        $mimeType = $attachment->mime_type;

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $disk->delete($attachment->storage_key);

            return;
        }

        $image = @imagecreatefromstring($disk->get($attachment->storage_key));

        if ($image === false) {
            $disk->delete($attachment->storage_key);

            return;
        }

        for ($pass = 0; $pass < self::BLUR_PASSES; $pass++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }
        imagefilter($image, IMG_FILTER_PIXELATE, self::PIXEL_BLOCK, true);

        ob_start();
        match ($mimeType) {
            'image/png' => imagepng($image),
            'image/webp' => imagewebp($image),
            default => imagejpeg($image, null, 60),
        };
        $bytes = ob_get_clean();
        imagedestroy($image);

        $disk->put($attachment->storage_key, $bytes);
    }
}
