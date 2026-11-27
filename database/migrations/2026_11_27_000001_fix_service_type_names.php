<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * تصحيح أسماء أنواع الصيانة العربية. الصف engine_oil اتخزّن قبل كذا باسم علامات استفهام
 * (العربي ما وصل بشكل صحيح). هنا نصحّح الأسماء الموجودة فقط، ولا نضيف صفوف جديدة.
 * الأنواع الافتراضية تُزرع بـ ServiceTypeSeeder.
 */
return new class extends Migration
{
    /** @var array<string, array{name_ar: string, name_en: string}> */
    private const NAMES = [
        'engine_oil' => ['name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil'],
        'oil_filter' => ['name_ar' => 'فلتر الزيت', 'name_en' => 'Oil filter'],
        'brake_pads' => ['name_ar' => 'تيل الفرامل', 'name_en' => 'Brake pads'],
        'tires' => ['name_ar' => 'الإطارات', 'name_en' => 'Tires'],
    ];

    public function up(): void
    {
        foreach (self::NAMES as $code => $names) {
            DB::table('service_types')->where('code', $code)->update([
                'name_ar' => $names['name_ar'],
                'name_en' => $names['name_en'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // لا نرجع للأسماء المكسورة
    }
};
