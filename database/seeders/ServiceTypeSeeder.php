<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

/**
 * أنواع الصيانة الافتراضية. تشغيل آمن أكثر من مرة (updateOrCreate بالكود).
 * الفترات هنا قيم افتراضية للقالب، والمستخدم يعدّلها لسيارته.
 */
class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'engine_oil', 'name_ar' => 'زيت المحرك', 'name_en' => 'Engine oil', 'default_interval_km' => 10000, 'default_interval_months' => 12],
            ['code' => 'oil_filter', 'name_ar' => 'فلتر الزيت', 'name_en' => 'Oil filter', 'default_interval_km' => 10000, 'default_interval_months' => 12],
            ['code' => 'brake_pads', 'name_ar' => 'تيل الفرامل', 'name_en' => 'Brake pads', 'default_interval_km' => 30000, 'default_interval_months' => null],
            ['code' => 'tires', 'name_ar' => 'الإطارات', 'name_en' => 'Tires', 'default_interval_km' => 40000, 'default_interval_months' => 48],
        ];

        foreach ($types as $type) {
            ServiceType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
