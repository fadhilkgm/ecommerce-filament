<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

// Main Sales Overview Widget - Shows most important metrics
class SalesStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        // Get filters
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $dateDescription = $this->getDateFilterDescription($startDate, $endDate);

        // Core metrics
        $totalSales = $this->getTotalSales($startDate, $endDate);
        $totalAmount = $this->getTotalSalesAmount($startDate, $endDate);
        $totalProfit = $totalAmount - $this->getTotalExpenses($startDate, $endDate);

        // Trend calculations
        $averages = $this->calculateBasicAverages($startDate, $endDate);

        return [
            Stat::make('Total Sales', $totalSales)
                ->description('Sales ' . $dateDescription)
                ->descriptionIcon($this->getTrendIcon($totalSales, $averages['sales_count']))
                ->color($this->getTrendColor($totalSales, $averages['sales_count'])),

            Stat::make('Total Revenue', number_format($totalAmount, 2))
                ->description('Revenue ' . $dateDescription)
                ->descriptionIcon($this->getTrendIcon($totalAmount, $averages['sales_amount']))
                ->color($this->getTrendColor($totalAmount, $averages['sales_amount'])),

            Stat::make('Net Profit', number_format($totalProfit, 2))
                ->description('Profit ' . $dateDescription)
                ->descriptionIcon($this->getTrendIcon($totalProfit, $averages['profit']))
                ->color($this->getTrendColor($totalProfit, $averages['profit'])),
        ];
    }

    // Helper methods...
    private function getTotalSales($startDate, $endDate)
    {
        $query = Invoice::query();
        $this->applyDateFilters($query, $startDate, $endDate);
        return $query->count();
    }

    private function getTotalSalesAmount($startDate, $endDate)
    {
        $query = Invoice::query();
        $this->applyDateFilters($query, $startDate, $endDate);
        return $query->sum('total_amount');
    }

    private function getTotalExpenses($startDate, $endDate)
    {
        $query = Expense::query();
        $this->applyDateFilters($query, $startDate, $endDate);
        return $query->sum('amount');
    }

    private function calculateBasicAverages($startDate, $endDate): array
    {
        $periodStart = $startDate ?? now()->subMonths(3);
        $periodEnd = $endDate ?? now();
        $daysDifference = Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) ?: 1;

        $salesQuery = Invoice::query();
        $this->applyDateFilters($salesQuery, $periodStart, $periodEnd);
        $salesCount = $salesQuery->count();
        $salesAmount = $salesQuery->sum('total_amount');

        $expenseQuery = Expense::query();
        $this->applyDateFilters($expenseQuery, $periodStart, $periodEnd);
        $expenseAmount = $expenseQuery->sum('amount');

        return [
            'sales_count' => $salesCount / $daysDifference,
            'sales_amount' => $salesAmount / $daysDifference,
            'profit' => ($salesAmount - $expenseAmount) / $daysDifference,
        ];
    }

    // Reuse these helper methods from original class
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

    private function getDateFilterDescription($startDate, $endDate)
    {
        if ($startDate || $endDate) {
            return 'between ' . ($startDate ?? 'the beginning') . ' and ' . ($endDate ?? 'now');
        }
    }
}
