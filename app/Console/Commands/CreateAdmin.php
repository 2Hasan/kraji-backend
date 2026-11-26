<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;

/**
 * ينشئ حساب مشرف أول (أو أي مشرف) من سطر الأوامر. كلمة المرور تُدخل بشكل مخفي.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {name}';

    protected $description = 'إنشاء حساب مشرف للوحة الإدارة';

    public function handle(): int
    {
        $password = $this->secret('كلمة المرور (12 حرف على الأقل)');

        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->error('كلمة المرور قصيرة.');

            return self::FAILURE;
        }

        AdminUser::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $password,
            'role' => 'admin',
        ]);

        $this->info('تم إنشاء حساب المشرف.');

        return self::SUCCESS;
    }
}
