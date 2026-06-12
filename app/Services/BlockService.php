<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\BlockList;
use Illuminate\Support\Collection;
use Exception;

class BlockService
{

    public function getBlocksByCustomer(Customer $customer): Collection
    {
        return $customer->blockedUsers()->with('blocked')->latest()->get();
    }

    public function blockDriver(Customer $customer, int $driverId, ?string $reason): BlockList
    {
        $driver = Driver::findOrFail($driverId);

        // التحقق من عدم وجود حظر سابق
        if (BlockList::isBlocked($customer, $driver)) {
            throw new Exception('السائق محظور مسبقاً');
        }

        return BlockList::create([
            'blocker_id'   => $customer->id,
            'blocker_type' => Customer::class,
            'blocked_id'   => $driver->id,
            'blocked_type' => Driver::class,
            'reason'       => $reason,
        ]);
    }

    public function unblockDriver(Customer $customer, int $driverId): bool
    {
        return BlockList::where('blocker_id', $customer->id)
            ->where('blocker_type', Customer::class)
            ->where('blocked_id', $driverId)
            ->where('blocked_type', Driver::class)
            ->delete() > 0;
    }


    public function getBlocksByDriver(Driver $driver): Collection
    {
        return $driver->blockedUsers()->with('blocked')->latest()->get();
    }

    public function blockCustomer(Driver $driver, int $customerId, ?string $reason): BlockList
    {
        $customer = Customer::findOrFail($customerId);

        if (BlockList::isBlocked($driver, $customer)) {
            throw new Exception('الزبون محظور مسبقاً');
        }

        return BlockList::create([
            'blocker_id'   => $driver->id,
            'blocker_type' => Driver::class,
            'blocked_id'   => $customer->id,
            'blocked_type' => Customer::class,
            'reason'       => $reason,
        ]);
    }

    public function unblockCustomer(Driver $driver, int $customerId): bool
    {
        return BlockList::where('blocker_id', $driver->id)
            ->where('blocker_type', Driver::class)
            ->where('blocked_id', $customerId)
            ->where('blocked_type', Customer::class)
            ->delete() > 0;
    }
}
