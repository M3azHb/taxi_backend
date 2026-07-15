<?php

namespace App\Services;

use App\Models\BlockList;
use App\Models\CarType;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\Ride;
use App\Models\Setting;
use App\Models\Tracking;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RideService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected DiscountCodeService $discountCodeService
    ) {
    }

    /* =========================================================
     |  ESTIMATE
     |========================================================= */

    /**
     * Estimate fare, distance and duration for a potential ride,
     * applying a discount code if provided.
     */
    public function estimateFare(array $data): array
    {
        $carType = CarType::findOrFail($data['car_type_id']);

        $distanceKm = $this->calculateDistance(
            $data['pickup_latitude'],
            $data['pickup_longitude'],
            $data['destination_latitude'],
            $data['destination_longitude']
        );

        $subtotal = $carType->calculateFare($distanceKm);

        // assume average speed of 30 km/h
        $durationMinutes = (int) ceil(($distanceKm / 30) * 60);

        $discountAmount = 0;

        if (!empty($data['discount_code'])) {
            try {
                $discount = $this->discountCodeService->validateCode($data['discount_code']);
                $discountAmount = $discount->calculateDiscount($subtotal);
            } catch (Exception $e) {
                // كود غير صالح / غير موجود → تجاهلي الخصم بهدوء (مش error هون لأنه estimate)
            }
        }

        $totalFare = $subtotal - $discountAmount;

        return [
            // المسافة والمدة (ليست مبالغ مالية)
            'distance_km'      => round($distanceKm, 2),
            'duration_minutes' => $durationMinutes,

            // تفاصيل الأجرة (مبالغ مالية بعملة currency)
            'base_fare'        => round((float) $carType->base_fare, 2),
            'distance_fare'    => round($subtotal - $carType->base_fare, 2),
            'subtotal'         => round($subtotal, 2),
            'discount_amount'  => round($discountAmount, 2),
            'total_fare'       => round($totalFare, 2),

            // رمز العملة الموحّد (من الإعدادات) — يستخدمه التطبيق للعرض
            'currency'         => self::currencySymbol(),
        ];
    }

    /**
     * رمز العملة المعروض، مشتقّ من إعداد app_currency.
     */
    public static function currencySymbol(): string
    {
        $code = Setting::get('app_currency', 'SYP');

        return match ($code) {
            'SYP' => 'ل.س',
            'USD' => '\$',
            'EUR' => '€',
            'TRY' => '₺',
            default => $code,
        };
    }

    /**
     * Haversine formula to calculate distance (in km) between two coordinates.
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /* =========================================================
     |  CREATE RIDE
     |========================================================= */

    /**
     * Create a new ride request from a customer to a specific driver.
     *
     * NOTE (fix): the `rides` table only has `pickup_address` /
     * `destination_address` columns (NOT address_pickup / address_destination),
     * and it has NO discount_code_id / discount_amount columns
     * (those belong to `payments`).
     */
    public function createRide(Customer $customer, array $data): Ride
    {
        if (empty($data['pickup_latitude']) || empty($data['pickup_longitude'])) {
        throw new Exception('موقع نقطة الانطلاق مطلوب');
    }
        return DB::transaction(function () use ($customer, $data) {
            // 1. العميل يجب ألا يملك رحلة نشطة بالفعل
            if ($customer->rides()->active()->exists()) {
                throw new Exception('لديك رحلة نشطة بالفعل');
            }

            // 2. حساب المسافة والأجرة التقديرية
            $distanceKm = $this->calculateDistance(
                $data['pickup_latitude'],
                $data['pickup_longitude'],
                $data['destination_latitude'],
                $data['destination_longitude']
            );

            $carType = CarType::findOrFail($data['car_type_id']);
            $estimatedFare = $carType->calculateFare($distanceKm);

            // 2.b حفظ كود الخصم مع الرحلة ليُطبَّق عند إنشاء الدفعة بعد الاكتمال.
            //     (الكود غير الصالح يُتجاهل بهدوء — لا نُفشل الحجز بسببه)
            $discountCodeId = null;
            if (! empty($data['discount_code'])) {
                try {
                    $discountCodeId = $this->discountCodeService
                        ->validateCode($data['discount_code'])->id;
                } catch (Exception $e) {
                    $discountCodeId = null;
                }
            }

            // 3. إنشاء الطلب بلا سائق — سيُبَثّ لكل السائقين المتاحين
            $ride = Ride::create([
                'customer_id'          => $customer->id,
                'driver_id'            => null,
                'car_type_id'          => $data['car_type_id'],
                'discount_code_id'     => $discountCodeId,
                'pickup_latitude'      => $data['pickup_latitude'],
                'pickup_longitude'     => $data['pickup_longitude'],
                'pickup_address'       => $data['pickup_address'] ?? null,
                'destination_latitude' => $data['destination_latitude'],
                'destination_longitude'=> $data['destination_longitude'],
                'destination_address'  => $data['destination_address'] ?? null,
                'distance_km'          => round($distanceKm, 2),
                'estimated_fare'       => round($estimatedFare, 2),
                'status'               => Ride::STATUS_PENDING,
                'requested_at'         => now(),
            ]);

            // 4. بثّ الطلب: إشعار كل السائقين المتاحين المطابقين لنوع السيارة (وغير المحظورين)
            $drivers = Driver::online()
                ->whereHas('car', fn ($q) => $q->where('car_type_id', $data['car_type_id']))
                ->get();

            foreach ($drivers as $driver) {
                if (BlockList::isBlockedEither($customer, $driver)) {
                    continue;
                }
                $this->notificationService->send(
                    $driver,
                    Notification::TYPE_NEW_RIDE_REQUEST,
                    'طلب رحلة جديد',
                    "لديك طلب من {$customer->name}",
                    ['ride_id' => $ride->id]
                );
            }

            return $ride;
        });
    }

    /* =========================================================
     |  CUSTOMER - READ
     |========================================================= */

    public function getActiveRideForCustomer(Customer $customer): ?Ride
    {
        return $customer->rides()
            ->active()
            ->with(['driver.car.carType', 'driver.location'])
            ->latest('requested_at')
            ->first();
    }

    public function getRideForCustomer(Customer $customer, int $rideId): Ride
    {
        return $customer->rides()
            ->with(['driver.car.carType', 'car.carType', 'payment', 'rating'])
            ->findOrFail($rideId);
    }

    public function getRideHistoryForCustomer(Customer $customer, array $filters): LengthAwarePaginator
    {
        $query = $customer->rides()->with(['driver.car', 'car'])->latest();

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /* =========================================================
     |  CUSTOMER - CANCEL
     |========================================================= */

    public function cancelRideByCustomer(Customer $customer, int $rideId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($customer, $rideId, $reason) {
            $ride = $customer->rides()->findOrFail($rideId);

            if (!$ride->canBeCancelled()) {
                throw new Exception('لا يمكن إلغاء الرحلة في هذه المرحلة');
            }

            $ride->update([
                'status'              => Ride::STATUS_CANCELLED,
                'cancelled_by'        => 'customer',
                'cancellation_reason' => $reason,
                'cancelled_at'        => now(),
            ]);

            // free the driver if he was busy because of this ride
            if ($ride->driver && $ride->driver->availability === Driver::AVAILABILITY_BUSY) {
                $ride->driver->update(['availability' => Driver::AVAILABILITY_ONLINE]);
            }

            if ($ride->driver) {
                $this->notificationService->send(
                    $ride->driver,
                    Notification::TYPE_RIDE_CANCELLED,
                    'تم إلغاء الرحلة',
                    'قام الزبون بإلغاء الرحلة',
                    ['ride_id' => $ride->id]
                );
            }

            return true;
        });
    }

    /* =========================================================
     |  TRACKING
     |========================================================= */

    public function getRideTracking(Customer $customer, int $rideId): array
    {
        $ride = $customer->rides()->findOrFail($rideId);

        $driverLocation = $ride->driver?->location;

        $path = $ride->trackings()->orderBy('recorded_at')->get();

        return [
            'driver_location' => $driverLocation,
            'tracking_path'   => $path,
        ];
    }

    /**
     * Record a single GPS point during an in-progress ride.
     *
     * NOTE (fix): use `recorded_at` (not `created_at`) for the timestamp
     * field, and make sure it's added to Tracking::$fillable and $casts.
     */
    public function recordTrackingPoint(Driver $driver, int $rideId, array $data): Tracking
    {
        $ride = $driver->rides()
            ->where('status', Ride::STATUS_IN_PROGRESS)
            ->findOrFail($rideId);

        return Tracking::create([
            'ride_id'     => $ride->id,
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'recorded_at' => now(),
        ]);
    }

    /* =========================================================
     |  RATING
     |========================================================= */

    public function rateRide(Customer $customer, int $rideId, array $data): Rating
    {
        $ride = $customer->rides()->findOrFail($rideId);

        if (!$ride->canBeRated()) {
            throw new Exception('لا يمكن تقييم هذه الرحلة');
        }

        // Driver average is updated automatically via Rating::booted() event.
        return Rating::create([
            'ride_id'     => $ride->id,
            'customer_id' => $customer->id,
            'driver_id'   => $ride->driver_id,
            'score'       => $data['score'],
            'comment'     => $data['comment'] ?? null,
        ]);
    }

    public function getDriverRatings(Driver $driver): LengthAwarePaginator
    {
        return Rating::where('driver_id', $driver->id)
            ->with('customer:id,name')
            ->latest()
            ->paginate(20);
    }

    public function getDriverRatingsSummary(Driver $driver): array
    {
        return [
            'average'      => (float) $driver->rating_average,
            'total_count'  => (int) $driver->rating_count,
            'distribution' => Rating::where('driver_id', $driver->id)
                ->select('score', DB::raw('count(*) as count'))
                ->groupBy('score')
                ->pluck('count', 'score')
                ->toArray(),
        ];
    }

    /* =========================================================
     |  DRIVER - READ
     |========================================================= */

    public function getPendingRidesForDriver(Driver $driver): Collection
    {
        // مراعاة الحالة: السائق غير النشط (offline) أو المنشغل برحلة (busy)
        // لا يرى أي طلبات. isAvailable() = is_active && availability === online.
        if (! $driver->isAvailable()) {
            return collect();
        }

        // نموذج البثّ: كل الطلبات المعلّقة غير المُسنَدة لأي سائق،
        // المطابقة لنوع سيارة هذا السائق، وغير المحظورة معه.
        $carTypeId = $driver->car?->car_type_id;

        return Ride::query()
            ->where('status', Ride::STATUS_PENDING)
            ->whereNull('driver_id')
            ->when($carTypeId, fn ($q) => $q->where('car_type_id', $carTypeId))
            ->with(['customer', 'carType'])
            ->latest('requested_at')
            ->get()
            ->reject(fn (Ride $ride) => BlockList::isBlockedEither($ride->customer, $driver))
            ->values();
    }

    public function getActiveRideForDriver(Driver $driver): ?Ride
    {
        return $driver->rides()
            ->active()
            ->with(['customer', 'car.carType'])
            ->first();
    }

    public function getRideForDriver(Driver $driver, int $rideId): Ride
    {
        return $driver->rides()
            ->with(['customer', 'car.carType', 'payment', 'rating'])
            ->findOrFail($rideId);
    }

    public function getRideHistoryForDriver(Driver $driver, array $filters): LengthAwarePaginator
    {
        // نُحمّل الدفعة أيضاً حتى يعرض تطبيق السائق أرباحه الصافية (driver_earning).
        $query = $driver->rides()->with(['customer', 'car', 'payment'])->latest();

        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /* =========================================================
     |  DRIVER - ACCEPT / REJECT / ARRIVED / START / COMPLETE / CANCEL
     |========================================================= */

    public function acceptRide(Driver $driver, int $rideId): Ride
    {
        return DB::transaction(function () use ($driver, $rideId) {
            if ($driver->availability !== Driver::AVAILABILITY_ONLINE) {
                throw new Exception('يجب أن تكون متصلًا لقبول الرحلات');
            }

            if ($driver->rides()->active()->exists()) {
                throw new Exception('لديك رحلة نشطة بالفعل');
            }

            // قفل الصف: أول سائق يقبل يفوز (first-come-first-served).
            // whereNull('driver_id') يضمن أن الطلب لم يُسنَد بعد لأحد.
            $ride = Ride::where('id', $rideId)
                ->where('status', Ride::STATUS_PENDING)
                ->whereNull('driver_id')
                ->lockForUpdate()
                ->first();

            if (! $ride) {
                throw new Exception('هذا الطلب لم يعد متاحاً');
            }

            if (BlockList::isBlockedEither($ride->customer, $driver)) {
                throw new Exception('لا يمكنك قبول هذا الطلب');
            }

            $ride->update([
                'driver_id'   => $driver->id,
                'status'      => Ride::STATUS_ACCEPTED,
                'car_id'      => $driver->cars()->first()?->id,
                'accepted_at' => now(),
            ]);

            $driver->update(['availability' => Driver::AVAILABILITY_BUSY]);

            $this->notificationService->send(
                $ride->customer,
                Notification::TYPE_RIDE_ACCEPTED,
                'تم قبول رحلتك',
                "{$driver->name} في الطريق إليك",
                ['ride_id' => $ride->id]
            );

            return $ride->fresh(['driver.car.carType']);
        });
    }

    public function rejectRide(Driver $driver, int $rideId, ?string $reason = null): bool
    {
        // في نموذج البثّ: "الرفض" مجرد تجاهل من طرف هذا السائق، ولا يُلغي
        // الطلب لبقية السائقين (يبقى معلّقاً حتى يقبله أحدهم أو يُلغيه العميل).
        // إن كان الطلب مُسنَداً لهذا السائق فعلاً (قبله ثم تراجع) نعيده للمجمّع.
        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driver->id)
            ->where('status', Ride::STATUS_ACCEPTED)
            ->first();

        if ($ride) {
            $ride->update([
                'driver_id'   => null,
                'status'      => Ride::STATUS_PENDING,
                'accepted_at' => null,
            ]);
            $driver->update(['availability' => Driver::AVAILABILITY_ONLINE]);
        }

        return true;
    }

    public function markDriverArrived(Driver $driver, int $rideId): Ride
    {
        $ride = $driver->rides()
            ->where('status', Ride::STATUS_ACCEPTED)
            ->findOrFail($rideId);

        $ride->update([
            'status'             => Ride::STATUS_DRIVER_ARRIVED,
            'driver_arrived_at'  => now(),
        ]);

        $this->notificationService->send(
            $ride->customer,
            Notification::TYPE_DRIVER_ARRIVED,
            'السائق بانتظارك',
            'وصل السائق إلى موقعك',
            ['ride_id' => $ride->id]
        );

        return $ride->fresh();
    }

    public function startRide(Driver $driver, int $rideId): Ride
    {
        $ride = $driver->rides()
            ->where('status', Ride::STATUS_DRIVER_ARRIVED)
            ->findOrFail($rideId);

        $ride->update([
            'status'     => Ride::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $this->notificationService->send(
            $ride->customer,
            Notification::TYPE_RIDE_STARTED,
            'بدأت الرحلة',
            'انطلقت رحلتك الآن',
            ['ride_id' => $ride->id]
        );

        return $ride->fresh();
    }

    public function completeRide(Driver $driver, int $rideId, array $data, PaymentService $paymentService): array
    {
        return DB::transaction(function () use ($driver, $rideId, $data, $paymentService) {
            $ride = $driver->rides()
                ->where('status', Ride::STATUS_IN_PROGRESS)
                ->findOrFail($rideId);

            // السعر مُثبَّت من لحظة الحجز: هو نفسه الذي رآه العميل ووافق عليه،
            // ومحسوب من مسافته (انطلاق ← وجهة). لا نُعيد الحساب ولا نثق بأي
            // مسافة يرسلها تطبيق السائق — هكذا يستحيل أن تتضخّم الأجرة.
            // ملاحظة: distance_km تبقى كما حُسبت وقت الحجز (لا نلمسها).
            $ride->update([
                'status'           => Ride::STATUS_COMPLETED,
                'completed_at'     => now(),
                'final_fare'       => $ride->estimated_fare,
                'duration_minutes' => $data['duration_minutes'] ?? null,
            ]);

            $payment = $paymentService->createPayment($ride);

            $driver->update(['availability' => Driver::AVAILABILITY_ONLINE]);

            $this->notificationService->send(
                $ride->customer,
                Notification::TYPE_RIDE_COMPLETED,
                'انتهت الرحلة',
                'تم إنهاء رحلتك بنجاح',
                ['ride_id' => $ride->id]
            );

            return ['ride' => $ride->fresh(), 'payment' => $payment];
        });
    }

    public function cancelRideByDriver(Driver $driver, int $rideId, ?string $reason = null): bool
    {
        $ride = $driver->rides()->findOrFail($rideId);

        if (!$ride->canBeCancelled()) {
            throw new Exception('لا يمكن إلغاء الرحلة في هذه المرحلة');
        }

        $ride->update([
            'status'              => Ride::STATUS_CANCELLED,
            'cancelled_by'        => 'driver',
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);

        $driver->update(['availability' => Driver::AVAILABILITY_ONLINE]);

        $this->notificationService->send(
            $ride->customer,
            Notification::TYPE_RIDE_CANCELLED,
            'تم إلغاء الرحلة',
            'قام السائق بإلغاء الرحلة',
            ['ride_id' => $ride->id]
        );

        return true;
    }
}
