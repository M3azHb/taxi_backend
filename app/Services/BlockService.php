<?php

namespace App\Services;

use App\Models\BlockList;
use App\Models\Customer;
use App\Models\Driver;
use Exception;
use Illuminate\Support\Collection;

class BlockService
{
    /**
     * ============ Customer blocking Driver ============
     */

    /**
     * Get all drivers blocked by this customer.
     */
    public function getBlocksByCustomer(Customer $customer): Collection
    {
        return $customer->blockedUsers()
            ->with('blocked')
            ->latest()
            ->get();
    }

    /**
     * Customer blocks a driver.
     */
    public function blockDriver(Customer $customer, int $driverId, ?string $reason = null): BlockList
    {
        $driver = Driver::findOrFail($driverId);

        if (BlockList::isBlocked($customer, $driver)) {
            throw new Exception('السائق محظور مسبقًا');
        }

        return BlockList::create([
            'blocker_id'   => $customer->id,
            'blocker_type' => Customer::class,
            'blocked_id'   => $driver->id,
            'blocked_type' => Driver::class,
            'reason'       => $reason,
        ]);
    }

    /**
     * Customer unblocks a driver.
     */
    public function unblockDriver(Customer $customer, int $driverId): bool
    {
        return BlockList::where('blocker_id', $customer->id)
            ->where('blocker_type', Customer::class)
            ->where('blocked_id', $driverId)
            ->where('blocked_type', Driver::class)
            ->delete() > 0;
    }

    /**
     * ============ Driver blocking Customer ============
     */

    /**
     * Get all customers blocked by this driver.
     */
    public function getBlocksByDriver(Driver $driver): Collection
    {
        return $driver->blockedUsers()
            ->with('blocked')
            ->latest()
            ->get();
    }

    /**
     * Driver blocks a customer.
     */
    public function blockCustomer(Driver $driver, int $customerId, ?string $reason = null): BlockList
    {
        $customer = Customer::findOrFail($customerId);

        if (BlockList::isBlocked($driver, $customer)) {
            throw new Exception('الزبون محظور مسبقًا');
        }

        return BlockList::create([
            'blocker_id'   => $driver->id,
            'blocker_type' => Driver::class,
            'blocked_id'   => $customer->id,
            'blocked_type' => Customer::class,
            'reason'       => $reason,
        ]);
    }

    /**
     * Driver unblocks a customer.
     */
    public function unblockCustomer(Driver $driver, int $customerId): bool
    {
        return BlockList::where('blocker_id', $driver->id)
            ->where('blocker_type', Driver::class)
            ->where('blocked_id', $customerId)
            ->where('blocked_type', Customer::class)
            ->delete() > 0;
    }
}
