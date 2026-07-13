<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Report;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * استقبال بيانات البلاغ من الزبون وحفظها في DB
     */
    public function submitReportByCustomer(Customer $customer, array $data): Report
    {
        return Report::create([
            'ride_id'       => $data['ride_id'] ?? null,
            'reporter_id'   => $customer->id,
            'reporter_type' => Customer::class,
            'reported_id'   => $data['reported_id'],
            'reported_type' => Driver::class,
            'description'   => $data['description'],
            'status'        => Report::STATUS_PENDING,
        ]);
    }

    /**
     * جلب قائمة البلاغات التي قدمها زبون
     */
    public function getReportsByCustomer(Customer $customer): Collection
    {
        return $customer->submittedReports()
            ->with(['reported', 'ride'])   // استخدمنا eager loading
            ->latest()
            ->get();
    }

    /**
     * استقبال بيانات البلاغ من السائق وحفظها في DB
     */
    public function submitReportByDriver(Driver $driver, array $data): Report
    {
        return Report::create([
            'ride_id'       => $data['ride_id'] ?? null,
            'reporter_id'   => $driver->id,
            'reporter_type' => Driver::class,
            'reported_id'   => $data['reported_id'],
            'reported_type' => Customer::class,
            'description'   => $data['description'],
            'status'        => Report::STATUS_PENDING,
        ]);
    }

    /**
     * جلب قائمة البلاغات التي قدمها زبون
     */
    public function getReportsByDriver(Driver $driver): Collection
    {
        return $driver->submittedReports()
            ->with(['reported', 'ride'])
            ->latest()
            ->get();
    }
}
