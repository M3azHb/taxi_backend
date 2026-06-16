<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Payment;
use App\Models\Ride;
use Illuminate\Support\Facades\DB;

class EarningsService
{
    /**
     * Summary of driver's earnings for a given period (today/week/month/all).
     */
    public function getSummary(Driver $driver, string $period): array
    {
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => null,
        };

        $payments = Payment::whereHas('ride', fn ($q) => $q->where('driver_id', $driver->id)
                ->where('status', Ride::STATUS_COMPLETED))
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->with('ride')
            ->get();

        return [
            'period'            => $period,
            'total_earnings'    => round((float) $payments->sum('driver_earning'), 2),
            'rides_count'       => $payments->count(),
            'total_distance_km' => round((float) $payments->sum(fn ($p) => $p->ride->distance_km ?? 0), 2),
            'average_per_ride'  => $payments->count() > 0
                ? round((float) $payments->avg('driver_earning'), 2)
                : 0,
            'commission_owed'   => round((float) $payments->where('status', Payment::STATUS_PENDING)->sum('commission_amount'), 2),
        ];
    }

    /**
     * Chart data (earnings per day) for a given period (week/month).
     */
    public function getChart(Driver $driver, string $period): array
    {
        $start = match ($period) {
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $payments = Payment::whereHas('ride', fn ($q) => $q->where('driver_id', $driver->id)
                ->where('status', Ride::STATUS_COMPLETED))
            ->where('created_at', '>=', $start)
            ->get();

        return $payments
            ->groupBy(fn ($p) => $p->created_at->format('Y-m-d'))
            ->map(function ($group, $date) {
                return [
                    'date'        => $date,
                    'earnings'    => round((float) $group->sum('driver_earning'), 2),
                    'rides_count' => $group->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Total commission currently owed by the driver to the platform.
     */
    public function getCommissionOwed(Driver $driver): array
    {
        $payments = Payment::whereHas('ride', fn ($q) => $q->where('driver_id', $driver->id))
            ->where('status', Payment::STATUS_PENDING)
            ->get();

        return [
            'total_owed'  => round((float) $payments->sum('commission_amount'), 2),
            'rides_count' => $payments->count(),
            'warning'     => $payments->sum('commission_amount') > 50000, // threshold example
        ];
    }
}
