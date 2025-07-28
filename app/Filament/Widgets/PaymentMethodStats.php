<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PaymentMethodStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $dateDescription = $this->getDateFilterDescription($startDate, $endDate);

        $averages = $this->calculatePaymentAverages($startDate, $endDate);

        $paymentMethods = ['upi', 'card', 'cash'];
        $paymentBreakdown = [];

        foreach ($paymentMethods as $method) {
            $query = Transaction::where('type', 'debit')
                ->where('transaction_type', 'sales')
                ->where('payment_method', $method);

            $this->applyDateFilters($query, $startDate, $endDate);

            $paymentBreakdown[$method] = $query->sum('amount');
        }

        $paymentIcons = [
            'upi' => 'heroicon-o-qr-code',
            'card' => 'heroicon-o-credit-card',
            'cash' => 'heroicon-o-banknotes',
        ];

        return [
            Stat::make('UPI Payments', number_format($paymentBreakdown['upi'], 2))
                ->description('UPI payments ' . $dateDescription)
                ->icon($paymentIcons['upi'])
                ->descriptionIcon($this->getTrendIcon($paymentBreakdown['upi'], $averages['upi']))
                ->color($this->getTrendColor($paymentBreakdown['upi'], $averages['upi'])),

            Stat::make('Card Payments', number_format($paymentBreakdown['card'], 2))
                ->description('Card payments ' . $dateDescription)
                ->icon($paymentIcons['card'])
                ->descriptionIcon($this->getTrendIcon($paymentBreakdown['card'], $averages['card']))
                ->color($this->getTrendColor($paymentBreakdown['card'], $averages['card'])),

            Stat::make('Cash Payments', number_format($paymentBreakdown['cash'], 2))
                ->description('Cash payments ' . $dateDescription)
                ->icon($paymentIcons['cash'])
                ->descriptionIcon($this->getTrendIcon($paymentBreakdown['cash'], $averages['cash']))
                ->color($this->getTrendColor($paymentBreakdown['cash'], $averages['cash'])),
        ];
    }

    private function calculatePaymentAverages($startDate, $endDate): array
    {
        $periodStart = $startDate ?? now()->subMonths(3);
        $periodEnd = $endDate ?? now();
        $daysDifference = Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) ?: 1;

        $methods = ['upi', 'card', 'cash'];
        $averages = [];

        foreach ($methods as $method) {
            $averages[$method] = Transaction::where('type', 'debit')
                ->where('transaction_type', 'sales')
                ->where('payment_method', $method)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->sum('amount') / $daysDifference;
        }

        return $averages;
    }

    private function getTrendIcon($value, $average, $invertComparison = false): string
    {
        if ($value == $average) {
            return 'heroicon-m-minus';
        }

        $isAboveAverage = $value > $average;

        if ($invertComparison) {
            $isAboveAverage = !$isAboveAverage;
        }

        return $isAboveAverage
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }

    private function getTrendColor($value, $average, $invertComparison = false): string
    {
        if ($value == $average) {
            return 'gray';
        }

        $isAboveAverage = $value > $average;

        if ($invertComparison) {
            $isAboveAverage = !$isAboveAverage;
        }

        return $isAboveAverage ? 'success' : 'danger';
    }

    private function applyDateFilters($query, $startDate, $endDate, $dateColumn = 'date'): void
    {
        if ($startDate) {
            $query->where($dateColumn, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($dateColumn, '<=', $endDate);
        }
    }

    private function getDateFilterDescription($startDate, $endDate): string
    {
        if ($startDate || $endDate) {
            return 'between ' . ($startDate ?? 'the beginning') . ' and ' . ($endDate ?? 'now');
        }

        return '(all time)';
    }
}
