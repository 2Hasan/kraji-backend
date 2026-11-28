<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * يقرأ ملف القالب: إما SVG وحده، أو ZIP فيه SVG في الجذر وفولدر FONTS فيه الخطوط (ttf / otf).
 * اسم كل ملف خط (بدون الامتداد) هو اسم العائلة اللي يستخدمه الـ SVG في font-family.
 */
class PlateTemplateBundle
{
    private const MAX_FONTS = 10;

    private const MAX_FONT_BYTES = 2 * 1024 * 1024;

    /** @return array{svg: string, fonts: array<string, string>} الخطوط: اسم الملف => المحتوى */
    public static function read(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            return ['svg' => file_get_contents($file->getRealPath()), 'fonts' => []];
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            self::fail('ملف ZIP ما يتفتح.');
        }

        $svg = null;
        $fonts = [];
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with($name, '/')) {
                    continue;
                }

                if (! str_contains($name, '/') && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'svg') {
                    $svg ??= $zip->getFromIndex($i);

                    continue;
                }

                if (preg_match('#^fonts/([A-Za-z0-9_-]+\.(?:ttf|otf))$#i', $name, $m)) {
                    if (count($fonts) >= self::MAX_FONTS) {
                        self::fail('أكثر من '.self::MAX_FONTS.' خطوط في الملف.');
                    }
                    $stat = $zip->statIndex($i);
                    if (($stat['size'] ?? 0) > self::MAX_FONT_BYTES) {
                        self::fail('الخط '.$m[1].' أكبر من 2 ميجا.');
                    }
                    $fonts[$m[1]] = $zip->getFromIndex($i);
                }
            }
        } finally {
            $zip->close();
        }

        if ($svg === null || $svg === false) {
            self::fail('ما فيه ملف SVG في جذر الـ ZIP.');
        }

        return ['svg' => $svg, 'fonts' => $fonts];
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['svg' => $message]);
    }
}
