<?php

namespace App\Filament\Widgets;

use App\Models\Driver;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Ride;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $ridesToday     = Ride::whereDate('created_at', today())->count();
        $driversOnline  = Driver::where('is_active', true)->where('availability', 'online')->count();
        $paymentsToday  = (float) Payment::whereDate('created_at', today())->sum('amount');
        $commissionOwed = (float) Payment::where('status', 'pending')->sum('commission_amount');
        $pendingReports = Report::where('status', 'pending')->count();

        return [
            Stat::make('رحلات اليوم', $ridesToday)
                ->descriptionIcon('heroicon-o-map')->color('info'),

            Stat::make('سائقون متصلون', $driversOnline)
                ->descriptionIcon('heroicon-o-truck')->color('success'),

            Stat::make('مدفوعات اليوم', number_format($paymentsToday) . ' ل.س')
                ->descriptionIcon('heroicon-o-banknotes')->color('primary'),

            Stat::make('عمولة مستحقة للشركة', number_format($commissionOwed) . ' ل.س')
                ->descriptionIcon('heroicon-o-receipt-percent')->color('warning'),

            Stat::make('بلاغات معلّقة', $pendingReports)
                ->descriptionIcon('heroicon-o-flag')->color($pendingReports > 0 ? 'danger' : 'gray'),
        ];
    }
}
