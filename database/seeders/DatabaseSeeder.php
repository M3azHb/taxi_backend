<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\CarType;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverLocation;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0) أدمن لوحة التحكم — email: admin@mishwar.app / password: password
        \App\Models\Admin::updateOrCreate(
            ['email' => 'admin@mishwar.app'],
            ['name' => 'مدير النظام', 'password' => Hash::make('password')]
        );

        // 1) إعدادات النظام
        Setting::set('commission_percentage', 10, 'decimal');
        Setting::set('app_currency', 'SYP', 'string');

        // 2) أنواع السيارات
        CarType::updateOrCreate(
            ['type_name' => 'Economy'],
            ['base_fare' => 2000, 'price_per_km' => 500, 'description' => 'اقتصادي · 4 مقاعد', 'is_active' => true]
        );
        $comfort = CarType::updateOrCreate(
            ['type_name' => 'Comfort'],
            ['base_fare' => 2200, 'price_per_km' => 550, 'description' => 'مريح · أحدث طراز', 'is_active' => true]
        );
        CarType::updateOrCreate(
            ['type_name' => 'Luxury'],
            ['base_fare' => 6800, 'price_per_km' => 900, 'description' => 'فاخر · 7 مقاعد', 'is_active' => true]
        );

        // 3) عميل تجريبي — phone: 0999000001 / password: password
        Customer::updateOrCreate(
            ['phone' => '0999000001'],
            [
                'name'      => 'أحمد العلي',
                'email'     => 'customer@mishwar.app',
                'password'  => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // 4) سائق تجريبي — phone: 0999000002 / password: password
        $driver = Driver::updateOrCreate(
            ['phone' => '0999000002'],
            [
                'name'         => 'سامر الحمصي',
                'email'        => 'driver@mishwar.app',
                'password'     => Hash::make('password'),
                'availability' => Driver::AVAILABILITY_ONLINE,
                'is_active'    => true,
            ]
        );

        // 5) سيارة للسائق التجريبي + موقعه الحالي
        Car::updateOrCreate(
            ['plate_number' => 'DAM 4128'],
            [
                'driver_id'          => $driver->id,
                'car_type_id'        => $comfort->id,
                'brand'              => 'Kia',
                'model'              => 'Cerato',
                'manufacturing_year' => 2021,
                'color'              => 'Silver',
            ]
        );

        DriverLocation::updateOrCreate(
            ['driver_id' => $driver->id],
            ['latitude' => 33.5138, 'longitude' => 36.2765, 'recorded_at' => now()]
        );
    }
}
