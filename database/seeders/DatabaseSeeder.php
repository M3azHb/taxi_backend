<?php

namespace Database\Seeders;

use App\Models\Admin;
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
    /**
     * Seed the database with a realistic set of demo accounts + settings.
     *
     * All demo accounts share the password  Mashwar@1234
     * (kept simple on purpose so the dashboard/mobile QA team can log in
     *  without hunting through docs). Rotate it before shipping to real users.
     */
    public function run(): void
    {
        $password = Hash::make('Mashwar@1234');

        // ─── 1) Dashboard admin ───────────────────────────────────────────
        Admin::updateOrCreate(
            ['email' => 'admin@mashwar.abukm.com'],
            ['name' => 'مدير النظام', 'password' => $password]
        );

        // ─── 2) System settings ───────────────────────────────────────────
        Setting::set('commission_percentage', 10, 'decimal');
        Setting::set('app_currency', 'SYP', 'string');

        // ─── 3) Car type — "سيارة عادية" is what all demo drivers use ────
        $carType = CarType::updateOrCreate(
            ['type_name' => 'سيارة عادية'],
            [
                'base_fare'    => 3000,
                'price_per_km' => 500,
                'description'  => 'سيارة صالون 4 ركاب',
                'is_active'    => true,
            ]
        );

        // ─── 4) Customers ─────────────────────────────────────────────────
        $customers = [
            ['محمد الأحمد',  'mohammad@mashwar.test', '0911111111'],
            ['فاطمة السيد',  'fatima@mashwar.test',   '0922222222'],
            ['أحمد الحمصي',  'ahmad@mashwar.test',    '0933333333'],
        ];
        foreach ($customers as [$name, $email, $phone]) {
            Customer::updateOrCreate(
                ['phone' => $phone],
                [
                    'name'      => $name,
                    'email'     => $email,
                    'password'  => $password,
                    'is_active' => true,
                ]
            );
        }

        // ─── 5) Drivers (+ their cars + last known GPS) ───────────────────
        // "سائق التجربة" is the driver that receives the 30-second demo
        // rides created by /root/scripts/mashwar/create-test-ride.sh on the
        // server. See DEPLOYMENT.md § "الطلبات التلقائية".
        $drivers = [
            ['عمر الشامي',   'omar_driver@mashwar.test',   '0944444444', '555 111'],
            ['خالد الدمشقي', 'khaled_driver@mashwar.test', '0955555555', '666 222'],
            ['سائق التجربة', 'test_driver@mashwar.test',   '0999999999', '999 999'],
        ];
        // A few Damascus spots to sprinkle drivers around instead of stacking
        // them on one dot in the map.
        $spawnPoints = [
            [33.5138, 36.2765], // ساحة الأمويين
            [33.5250, 36.3200], // ساحة العباسيين
            [33.5000, 36.2833], // كفرسوسة
        ];
        foreach ($drivers as $i => [$name, $email, $phone, $plate]) {
            $driver = Driver::updateOrCreate(
                ['phone' => $phone],
                [
                    'name'           => $name,
                    'email'          => $email,
                    'password'       => $password,
                    'availability'   => Driver::AVAILABILITY_ONLINE,
                    'is_active'      => true,
                    'rating_average' => 4.5,
                    'rating_count'   => 10,
                ]
            );

            Car::updateOrCreate(
                ['plate_number' => $plate],
                [
                    'driver_id'          => $driver->id,
                    'car_type_id'        => $carType->id,
                    'brand'              => 'Kia',
                    'model'              => 'Cerato',
                    'manufacturing_year' => 2022,
                    'color'              => 'أبيض',
                ]
            );

            [$lat, $lng] = $spawnPoints[$i % count($spawnPoints)];
            DriverLocation::updateOrCreate(
                ['driver_id' => $driver->id],
                ['latitude' => $lat, 'longitude' => $lng, 'recorded_at' => now()]
            );
        }
    }
}
