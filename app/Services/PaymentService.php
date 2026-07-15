<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Ride;
use App\Models\Setting;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * Create the Payment record for a just-completed ride.
     * Called from RideService::completeRide().
     *
     * NOTE (fix): Payment::$fillable must include subtotal, commission_percentage,
     * commission_amount, driver_earning, payment_method (besides ride_id,
     * discount_code_id, amount, discount_amount, status, paid_at).
     */
    public function createPayment(Ride $ride): Payment
    {
        $commissionPercentage = (float) Setting::get('commission_percentage', 10);

        $subtotal = (float) $ride->final_fare;

        // الخصم يُقرأ من كود الخصم المحفوظ على الرحلة لحظة الحجز.
        // (سابقاً كان يُقرأ من $ride->discount_amount — وهو حقل غير موجود
        //  في جدول rides إطلاقاً، فكان الخصم دائماً صفراً.)
        $discountAmount = 0.0;
        $discountCodeId = null;
        $discount       = $ride->discountCode;

        if ($discount && $discount->isValid()) {
            $discountAmount = $discount->calculateDiscount($subtotal);
            $discountCodeId = $discount->id;
        }

        $amount           = round($subtotal - $discountAmount, 2);
        $commissionAmount = round($amount * ($commissionPercentage / 100), 2);
        $driverEarning    = round($amount - $commissionAmount, 2);

        $payment = Payment::create([
            'ride_id'               => $ride->id,
            'discount_code_id'      => $discountCodeId,
            'subtotal'              => $subtotal,
            'discount_amount'       => $discountAmount,
            'amount'                => $amount,
            'commission_percentage' => $commissionPercentage,
            'commission_amount'     => $commissionAmount,
            'driver_earning'        => $driverEarning,
            'payment_method'        => 'cash',
            'status'                => Payment::STATUS_PENDING,
        ]);

        // تسجيل استخدام الكود — لهذا كانت لوحة التحكم تعرض "استُخدم: 0" دائماً.
        if ($discount) {
            $discount->incrementUsage();
        }

        return $payment;
    }

    /**
     * Driver confirms that he received the cash payment for a completed ride.
     */
    public function confirmCashPayment(Driver $driver, int $rideId): Payment
    {
        $ride = $driver->rides()->findOrFail($rideId);

        $payment = $ride->payment;

        if (!$payment) {
            throw new Exception('لا يوجد دفعة لهذه الرحلة');
        }

        $payment->markAsPaid();

        $this->notificationService->send(
            $ride->customer,
            Notification::TYPE_PAYMENT_CONFIRMED,
            'تم تأكيد الدفع',
            'تم تأكيد استلام مبلغ الرحلة',
            ['ride_id' => $ride->id]
        );

        return $payment->fresh();
    }

    /**
     * Paginated payment history for a driver.
     */
    public function getPaymentHistoryForDriver(Driver $driver, array $filters): LengthAwarePaginator
    {
        return Payment::whereHas('ride', fn ($q) => $q->where('driver_id', $driver->id))
            ->with('ride.customer')
            ->latest()
            ->paginate($filters['per_page'] ?? 20);
    }
}
