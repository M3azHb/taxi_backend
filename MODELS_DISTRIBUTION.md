# توزيع الـ Models على الفريق (4 أشخاص)

كل شخص مسؤول عن مجموعة من الـ Models. كل شخص يبني:
1. ملف الـ Model نفسه (في `app/Models/`)
2. تعريف `$fillable`, `$casts`, `$hidden`
3. تعريف العلاقات (Relationships)
4. أي Scopes أو Methods مساعدة

---

## ملاحظات مهمة قبل البدء

### 1. كل الـ Models ترث من `Illuminate\Database\Eloquent\Model`
ما عدا الـ Models التي تستخدم المصادقة (Customer, Driver, Admin)، فهي ترث من `Illuminate\Foundation\Auth\User`.

### 2. تنسيق ملف الـ Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
// ... باقي الـ imports

class ModelName extends Model
{
    protected $fillable = [...];

    protected $casts = [...];

    protected $hidden = [...];

    // العلاقات
    public function relationName(): HasMany
    {
        return $this->hasMany(...);
    }
}
```

### 3. عند الإشارة لـ Model من شخص آخر
استخدم class string الكامل، فهي تعمل حتى لو لم يُبنَ الـ Model بعد:
```php
return $this->belongsTo(\App\Models\Customer::class);
```

### 4. أوامر مفيدة لإنشاء Models
```bash
php artisan make:model Customer
php artisan make:model Driver
# ... وهكذا
```

---

# 👤 الشخص 1 — Identity Layer (المستخدمون)

**3 Models** — المسؤول عن كل ما يتعلق بحسابات المستخدمين.

## 1.1 Customer

**الملف:** `app/Models/Customer.php`

**يرث من:** `Authenticatable` (لأنه يسجل دخول)

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    // العلاقات
    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function blocks(): MorphMany
    {
        return $this->morphMany(BlockList::class, 'blocker');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reporter');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
```

---

## 1.2 Driver

**الملف:** `app/Models/Driver.php`

**يرث من:** `Authenticatable`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password',
        'rating_average', 'rating_count',
        'availability', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    // الحالات الثابتة
    public const AVAILABILITY_ONLINE = 'online';
    public const AVAILABILITY_OFFLINE = 'offline';
    public const AVAILABILITY_BUSY = 'busy';

    // العلاقات
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function car(): HasOne
    {
        // السيارة الأولى (الافتراضية)
        return $this->hasOne(Car::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(DriverLocation::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function blocks(): MorphMany
    {
        return $this->morphMany(BlockList::class, 'blocker');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reporter');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes مفيدة
    public function scopeOnline($query)
    {
        return $query->where('availability', self::AVAILABILITY_ONLINE)
                     ->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->online();
    }
}
```

---

## 1.3 Admin

**الملف:** `app/Models/Admin.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
```

---

## 📌 ملاحظات للشخص 1

- استخدم `Laravel Sanctum` للمصادقة → ثبّته أولاً: `composer require laravel/sanctum`.
- بعد التثبيت، نفّذ `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`.
- في `config/auth.php` ستحتاج إضافة guards جديدة (`customer`, `driver`, `admin`).
- **لا تنتظر باقي الـ Models** — استخدم class strings كما هو موضح.

---

# 🚗 الشخص 2 — Vehicles & Settings Layer

**4 Models** — كل ما يتعلق بالسيارات وإعدادات النظام.

## 2.1 CarType

**الملف:** `app/Models/CarType.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarType extends Model
{
    protected $fillable = [
        'type_name', 'base_fare', 'price_per_km',
        'description', 'is_active',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // حساب الأجرة بناءً على المسافة
    public function calculateFare(float $distanceKm): float
    {
        return (float) $this->base_fare + ($distanceKm * (float) $this->price_per_km);
    }
}
```

---

## 2.2 Car

**الملف:** `app/Models/Car.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    protected $fillable = [
        'driver_id', 'car_type_id', 'plate_number',
        'brand', 'model', 'manufacturing_year', 'color',
    ];

    protected $casts = [
        'manufacturing_year' => 'integer',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarType::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }
}
```

---

## 2.3 DriverLocation

**الملف:** `app/Models/DriverLocation.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id', 'latitude', 'longitude',
        'heading', 'speed', 'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'heading' => 'decimal:2',
        'speed' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    // حساب المسافة من نقطة معينة (Haversine formula)
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad((float) $this->latitude);
        $lngFrom = deg2rad((float) $this->longitude);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }
}
```

---

## 2.4 Setting

**الملف:** `app/Models/Setting.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'description',
    ];

    // الحصول على قيمة إعداد (مع cache)
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return match ($setting->type) {
                'integer' => (int) $setting->value,
                'decimal', 'float' => (float) $setting->value,
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    // تحديث إعداد
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );

        Cache::forget("setting.{$key}");
    }
}
```

---

## 📌 ملاحظات للشخص 2

- `Setting::get('commission_percentage')` ستُستخدم بكثرة في حسابات الدفع.
- `DriverLocation::distanceFrom()` ستُستخدم لحساب أقرب سائق للزبون.
- `CarType::calculateFare()` ستُستخدم في حساب الأجرة المتوقعة.

---

# 🛣️ الشخص 3 — Ride & Payment Layer (الجوهر)

**4 Models** — قلب النظام. الشخص الأكثر مسؤولية.

## 3.1 Ride

**الملف:** `app/Models/Ride.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ride extends Model
{
    protected $fillable = [
        'customer_id', 'driver_id', 'car_id', 'car_type_id',
        'pickup_latitude', 'pickup_longitude', 'pickup_address',
        'destination_latitude', 'destination_longitude', 'destination_address',
        'distance_km', 'duration_minutes', 'estimated_fare', 'final_fare',
        'status', 'cancelled_by', 'cancellation_reason',
        'requested_at', 'accepted_at', 'driver_arrived_at',
        'started_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:7',
        'pickup_longitude' => 'decimal:7',
        'destination_latitude' => 'decimal:7',
        'destination_longitude' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'integer',
        'estimated_fare' => 'decimal:2',
        'final_fare' => 'decimal:2',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'driver_arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // الحالات
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DRIVER_ARRIVED = 'driver_arrived';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarType::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(Tracking::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Helpers
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
        ]);
    }
}
```

---

## 3.2 Tracking

**الملف:** `app/Models/Tracking.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tracking extends Model
{
    protected $fillable = [
        'ride_id', 'latitude', 'longitude', 'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'recorded_at' => 'datetime',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
```

---

## 3.3 Rating

**الملف:** `app/Models/Rating.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'ride_id', 'customer_id', 'driver_id', 'score', 'comment',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    // عند إنشاء تقييم جديد، حدّث متوسط تقييم السائق
    protected static function booted(): void
    {
        static::created(function (Rating $rating) {
            $driver = $rating->driver;
            if (!$driver) return;

            $newCount = $driver->rating_count + 1;
            $newAverage = (($driver->rating_average * $driver->rating_count) + $rating->score) / $newCount;

            $driver->update([
                'rating_average' => round($newAverage, 2),
                'rating_count' => $newCount,
            ]);
        });
    }
}
```

---

## 3.4 Payment

**الملف:** `app/Models/Payment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ride_id', 'discount_code_id',
        'subtotal', 'discount_amount', 'amount',
        'commission_percentage', 'commission_amount', 'driver_earning',
        'payment_method', 'status', 'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'driver_earning' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
```

---

## 📌 ملاحظات للشخص 3

- جدول `rides` هو الأهم في النظام. خذ وقتك في فهم الحالات السبع.
- `Rating::booted()` يستخدم Eloquent Events لتحديث `rating_average` تلقائياً.
- استخدم `Setting::get('commission_percentage')` (من الشخص 2) عند إنشاء `Payment`.
- اطّلع على [`DATABASE.md`](DATABASE.md) — قسم "سيناريو رحلة كاملة".

---

# 💬 الشخص 4 — Social & Communication Layer

**4 Models** — كل ما يتعلق بالتفاعلات (حظر، بلاغات، إشعارات، خصومات).

## 4.1 DiscountCode

**الملف:** `app/Models/DiscountCode.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    protected $fillable = [
        'code', 'discount_percentage', 'expiry_date',
        'usage_limit', 'used_count', 'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'expiry_date' => 'date',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // التحقق من صلاحية الكود
    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expiry_date->isPast()) return false;
        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) return false;

        return true;
    }

    // حساب قيمة الخصم على مبلغ معين
    public function calculateDiscount(float $amount): float
    {
        return round($amount * ((float) $this->discount_percentage / 100), 2);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                     ->where('expiry_date', '>=', now()->toDateString());
    }
}
```

---

## 4.2 BlockList (Polymorphic)

**الملف:** `app/Models/BlockList.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlockList extends Model
{
    protected $fillable = [
        'blocker_id', 'blocker_type',
        'blocked_id', 'blocked_type',
        'reason',
    ];

    // العلاقات Polymorphic
    public function blocker(): MorphTo
    {
        return $this->morphTo();
    }

    public function blocked(): MorphTo
    {
        return $this->morphTo();
    }
}
```

---

## 4.3 Report (Polymorphic)

**الملف:** `app/Models/Report.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $fillable = [
        'ride_id',
        'reporter_id', 'reporter_type',
        'reported_id', 'reported_type',
        'description', 'status', 'admin_note',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function reporter(): MorphTo
    {
        return $this->morphTo();
    }

    public function reported(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
```

---

## 4.4 Notification (Polymorphic)

**الملف:** `app/Models/Notification.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $fillable = [
        'notifiable_id', 'notifiable_type',
        'title', 'body', 'type', 'data', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }
}
```

---

## 📌 ملاحظات للشخص 4

- الـ `morphTo()` تحتاج أن تكون أسماء الـ types في DB كاملة:
  - `\App\Models\Customer`
  - `\App\Models\Driver`
- مثال على إنشاء بلاغ:
```php
Report::create([
    'ride_id' => 100,
    'reporter_id' => 5,
    'reporter_type' => Customer::class,
    'reported_id' => 12,
    'reported_type' => Driver::class,
    'description' => '...',
]);
```
- مثال على استرجاع إشعارات زبون:
```php
$customer->notifications()->unread()->latest()->get();
```

---

# 📊 جدول التوزيع الموجز

| الشخص | المسؤولية | عدد Models | Models |
|------|-----------|-----------|--------|
| **1** | المستخدمون | 3 | Customer, Driver, Admin |
| **2** | المركبات والإعدادات | 4 | CarType, Car, DriverLocation, Setting |
| **3** | الرحلات والدفع | 4 | Ride, Tracking, Rating, Payment |
| **4** | التواصل والاجتماعية | 4 | DiscountCode, BlockList, Report, Notification |

---

# 🔄 خطة التنسيق بين الفريق

## 1. ابدأوا بنفس الوقت
كل شخص ينشئ ملفاته باستخدام:
```bash
php artisan make:model Customer
php artisan make:model Driver
# ... وهكذا
```

## 2. لا تنتظروا بعضكم
استخدموا class strings للإشارة لـ Models لم تُبنَ بعد:
```php
return $this->belongsTo(\App\Models\Driver::class);
```

## 3. اختبروا عملكم منفرداً
كل شخص يختبر models الخاصة به عبر:
```bash
php artisan tinker
```
```php
>>> App\Models\Customer::create(['name' => 'Test', 'email' => 'test@test.com', 'phone' => '+963900000000', 'password' => bcrypt('password')]);
```

## 4. اجتمعوا للدمج
بعد انتهاء الجميع:
- شغّلوا `php artisan migrate:fresh` معاً.
- اختبروا العلاقات بـ tinker:
```php
$customer = Customer::factory()->create();
$ride = Ride::factory()->for($customer)->create();
$customer->rides; // يجب أن يعرض الرحلة
```

## 5. الترتيب الموصى به داخل عمل كل شخص
1. أنشئ Model فارغ.
2. ضع `$fillable` و `$casts`.
3. أضف العلاقات (Relationships).
4. أضف الـ Scopes والـ Methods.
5. اختبر في tinker.

---

# ✅ Checklist لكل شخص

```
[ ] أنشأت ملف الـ Model باستخدام `php artisan make:model X`
[ ] أضفت `$fillable` بكل الحقول التي يمكن تعديلها
[ ] أضفت `$casts` للحقول الخاصة (boolean, date, decimal, json...)
[ ] أضفت `$hidden` للحقول الحساسة (password)
[ ] عرّفت كل العلاقات (BelongsTo, HasMany, HasOne, MorphTo, MorphMany)
[ ] أضفت Scopes مفيدة (active, online, valid...)
[ ] أضفت Methods مساعدة عند الحاجة
[ ] اختبرت Model في tinker
[ ] راجعت [DATABASE.md](DATABASE.md) للتأكد من تطابق الحقول
```

---

# 🚨 أخطاء شائعة لتجنبها

### 1. نسيان `$fillable`
سيمنع Laravel من حفظ البيانات (Mass Assignment Protection).

### 2. خطأ في نوع العلاقة
- `BelongsTo` للعلاقة من الـ child إلى الـ parent (يحوي FK).
- `HasMany` / `HasOne` من الـ parent إلى الـ child.

### 3. خطأ في أسماء العلاقات
Laravel يخمّن FK من اسم العلاقة. مثلاً:
```php
public function customer() // ← يبحث عن customer_id
{
    return $this->belongsTo(Customer::class);
}
```

### 4. نسيان `protected $casts` للـ enum
بدونها، الحقل يُعامل كـ string فقط.

### 5. خطأ في Polymorphic
أسماء الـ types يجب أن تكون كاملة مع namespace:
```php
'reporter_type' => Customer::class, // ✅ صحيح
'reporter_type' => 'Customer',       // ❌ خطأ
```

---

# 📚 موارد مفيدة

- **Eloquent Docs:** https://laravel.com/docs/12.x/eloquent
- **Relationships:** https://laravel.com/docs/12.x/eloquent-relationships
- **Polymorphic Relations:** https://laravel.com/docs/12.x/eloquent-relationships#polymorphic-relationships
