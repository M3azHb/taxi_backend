<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * إنشاء (أو إعادة تعيين كلمة مرور) حساب أدمن للوحة التحكم.
 * آمن للإنتاج: لا يمسّ أي بيانات أخرى، ويعمل بـ updateOrCreate.
 *
 * أمثلة:
 *   php artisan mashwar:create-admin
 *   php artisan mashwar:create-admin admin@mashwar.abukm.com "Mashwar@1234" "مدير النظام"
 */
class CreateAdmin extends Command
{
    protected $signature = 'mashwar:create-admin
                            {email? : بريد الأدمن}
                            {password? : كلمة المرور}
                            {name? : الاسم}';

    protected $description = 'إنشاء أو إعادة تعيين حساب أدمن للوحة التحكم';

    public function handle(): int
    {
        $email    = $this->argument('email')    ?: $this->ask('البريد الإلكتروني');
        $password = $this->argument('password') ?: $this->secret('كلمة المرور');
        $name     = $this->argument('name')     ?: 'مدير النظام';

        if (! $email || ! $password) {
            $this->error('البريد وكلمة المرور مطلوبان.');
            return self::FAILURE;
        }

        $admin = Admin::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        $this->info("✔ تم حفظ الأدمن: {$admin->email}");
        $this->line('يمكنك الآن الدخول على /admin بهذه البيانات.');

        return self::SUCCESS;
    }
}
