<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MonthlyBalance;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use App\Models\Transaction;

class FinancialFlowStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $dateDescription = $this->getDateFilterDescription($startDate, $endDate);

        $averages = $this->calculateTransactionAverages($startDate, $endDate);

        // Get transactions data
        $creditsQuery = Transaction::where('type', 'credit');
        $this->applyDateFilters($creditsQuery, $startDate, $endDate);
        $totalCredits = $creditsQuery->sum('amount');

        $debitsQuery = Transaction::where('type', 'debit');
        $this->applyDateFilters($debitsQuery, $startDate, $endDate);
        $totalDebits = $debitsQuery->sum('amount');

        $expenseQuery = Expense::query();
        $this->applyDateFilters($expenseQuery, $startDate, $endDate);
        $totalExpense = $expenseQuery->sum('amount');

        // Daily metrics
        $todaySales = Invoice::whereDate('created_at', Carbon::today())->count();
        $todayAmount = Invoice::whereDate('created_at', Carbon::today())->sum('total_amount');
        $openingBalance = MonthlyBalance::where('month', now()->startOfMonth())->first()?->opening_balance ?? 0;
        $closingBalance = MonthlyBalance::where('month', now()->startOfMonth())->first()?->closing_balance ?? 0;

        return [
            // Stat::make('Total Credits', number_format($totalCredits, 2))
            //     ->description('Credits ' . $dateDescription)
            //     ->icon('heroicon-o-arrow-uturn-up')
            //     ->descriptionIcon($this->getTrendIcon($totalCredits, $averages['credits']))
            //     ->color($this->getTrendColor($totalCredits, $averages['credits'])),

            // Stat::make('Total Debits', number_format($totalDebits, 2))
            //     ->description('Debits ' . $dateDescription)
            //     ->icon('heroicon-o-arrow-uturn-down')
            //     ->descriptionIcon($this->getTrendIcon($totalDebits, $averages['debits'], true))
            //     ->color($this->getTrendColor($totalDebits, $averages['debits'], true)),

            Stat::make('Total Expenses', number_format($totalExpense, 2))
                ->description('Expenses ' . $dateDescription)
                ->icon('heroicon-o-receipt-percent')
                ->descriptionIcon($this->getTrendIcon($totalExpense, $averages['expenses'], true))
                ->color($this->getTrendColor($totalExpense, $averages['expenses'], true)),
            Stat::make('Opening Balance', number_format($openingBalance, 3))
                ->description('Opening Balance of the month ' . now()->startOfMonth()->format('F')),
            Stat::make('Closing Balance', number_format($closingBalance, 3))
                ->description('Closing Balance of the month ' . now()->startOfMonth()->format('F')),
        ];
    }

    private function calculateTransactionAverages($startDate, $endDate): array
    {
        $periodStart = $startDate ?? now()->subMonths(3);
        $periodEnd = $endDate ?? now();
        $daysDifference = Carbon::parse($periodStart)->diffInDays(Carbon::parse($periodEnd)) ?: 1;

        $creditsAvg = Transaction::where('type', 'credit')
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('amount') / $daysDifference;

        $debitsAvg = Transaction::where('type', 'debit')
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('amount') / $daysDifference;

        $expenseAvg = Expense::whereBetween('date', [$periodStart, $periodEnd])
            ->sum('amount') / $daysDifference;

        return [
            'credits' => $creditsAvg,
            'debits' => $debitsAvg,
            'expenses' => $expenseAvg,
        ];
    }

    // Reuse helper methods from original class (same as other widgets)
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
