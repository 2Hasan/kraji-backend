<?php

namespace App\Services;

class PlateTemplateValidator
{
    /** علامات رسمية ممنوعة في نص القالب (علم، شعار، كلمات اللوحة الرسمية). */
    private const FORBIDDEN_TEXT = ['BAHRAIN', 'البحرين', 'POLICE', 'الشرطة'];

    /** أسماء عامة ما تحتاج ملف خط. */
    private const GENERIC_FONTS = ['serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'inherit'];

    /**
     * @param  array<int, string>  $fonts  أسماء ملفات الخطوط بدون الامتداد (من فولدر FONTS)
     * @return array<int, string> قائمة الأخطاء (فاضية = القالب سليم)
     */
    public function validate(string $svg, array $fonts = []): array
    {
        $errors = [];

        foreach ($this->fontFamiliesUsed($svg) as $family) {
            if (! in_array($family, $fonts, true)) {
                $errors[] = 'الخط "'.$family.'" في الـ SVG ما له ملف في فولدر FONTS. لازم يكون اسم ملف الخط بالضبط بدون الامتداد، والحروف الكبيرة والصغيرة مهمة.';
            }
        }

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

    /** @return array<int, string> أسماء الخطوط المستخدمة في font-family (بدون الأسماء العامة وبدون التكرار) */
    private function fontFamiliesUsed(string $svg): array
    {
        preg_match_all('/font-family\s*[:=]\s*["\']?([^"\';>]+)/i', $svg, $matches);

        $families = [];
        foreach ($matches[1] as $value) {
            foreach (explode(',', $value) as $name) {
                $name = trim(trim($name), "\"' \t\r\n");
                if ($name !== '' && ! in_array(strtolower($name), self::GENERIC_FONTS, true) && ! in_array($name, $families, true)) {
                    $families[] = $name;
                }
            }
        }

        return $families;
    }
}
