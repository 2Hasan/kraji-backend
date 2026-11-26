<?php

namespace App\Services;

class PlateTemplateValidator
{
    /** علامات رسمية ممنوعة في نص القالب (علم، شعار، كلمات اللوحة الرسمية). */
    private const FORBIDDEN_TEXT = ['BAHRAIN', 'البحرين', 'POLICE', 'الشرطة'];

    /** @return array<int, string> قائمة الأخطاء (فاضية = القالب سليم) */
    public function validate(string $svg): array
    {
        $errors = [];

        if (substr_count($svg, '{{plate_number}}') !== 1) {
            $errors[] = 'لازم يحتوي {{plate_number}} مرة وحدة.';
        }

        if (preg_match('/<\s*(script|image|foreignObject)\b/i', $svg)) {
            $errors[] = 'ما يُسمح بـ script أو image أو foreignObject.';
        }

        foreach (self::FORBIDDEN_TEXT as $word) {
            if (mb_stripos($svg, $word) !== false) {
                $errors[] = 'القالب يحتوي كلمة رسمية ممنوعة: '.$word;
            }
        }

        return $errors;
    }
}
