<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingPaymentsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $pendingPayments = Order::where('payment_status', 'pending')
            ->where('payment_method', 'manual_payment')
            ->count();

        $pendingAmount = Order::where('payment_status', 'pending')
            ->where('payment_method', 'manual_payment')
            ->sum('total_amount');

        return [
            Stat::make('Pending Manual Payments', $pendingPayments)
                ->description('Orders awaiting payment confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayments > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.orders.index', [
                    'tableFilters' => [
                        'pending_manual_payments' => ['isActive' => true]
                    ]
                ])),

            Stat::make('Pending Payment Value', '$' . number_format($pendingAmount, 2))
                ->description('Total value of pending payments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($pendingAmount > 0 ? 'warning' : 'success'),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }
}