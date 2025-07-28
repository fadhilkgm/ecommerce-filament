<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class TotalSalesChart extends ChartWidget
{
    protected static ?string $heading = 'Past Six Month Sales';

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        $invoices = Invoice::where('date', '>=', now()->subMonths(6))->orderBy('date', 'desc')->get();

        foreach ($invoices as $invoice) {
            $monthYear = date('M Y', strtotime($invoice->date));
            if (!in_array($monthYear, $labels)) {
                $labels[] = $monthYear;
            }
            $monthIndex = array_search($monthYear, $labels);
            $data[$monthIndex] = ($data[$monthIndex] ?? 0) + $invoice->total_amount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Sales',
                    'data' => array_reverse($data),
                ],
            ],
            'labels' => array_reverse($labels),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
