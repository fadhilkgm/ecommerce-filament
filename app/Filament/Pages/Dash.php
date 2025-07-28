<?php

namespace App\Filament\Pages;

use App\Models\MonthlyBalance;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;

class Dash extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function mount()
    {
        $currentMonth = now()->startOfMonth();

        // Check if already generated
        if (!MonthlyBalance::where('month', $currentMonth)->exists()) {
            $previousMonth = now()->subMonth()->startOfMonth();
            $prevBalance = MonthlyBalance::where('month', $previousMonth)->first();

            $opening = $prevBalance?->closing_balance ?? 0;

            $credits = Transaction::whereBetween('date', [$previousMonth, $previousMonth->copy()->endOfMonth()])
                ->where('type', 'credit')->sum('amount');

            $debits = Transaction::whereBetween('date', [$previousMonth, $previousMonth->copy()->endOfMonth()])
                ->where('type', 'debit')->sum('amount');

            $closing = $opening + $credits - $debits;

            MonthlyBalance::create([
                'month' => $previousMonth,
                'opening_balance' => $opening,
                'credits' => $credits,
                'debits' => $debits,
                'closing_balance' => $closing,
                'shop_id' => 1 ?? Filament::getTenant()->id
            ]);

            // Prepare current month opening (if not already)
            MonthlyBalance::updateOrCreate(
                [
                    'month' => $currentMonth,
                    'shop_id' => Filament::getTenant()->id
                ],
                ['opening_balance' => $closing]
            );
        }
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->maxDate(fn(Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->minDate(fn(Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(3),
            ]);
    }
}
