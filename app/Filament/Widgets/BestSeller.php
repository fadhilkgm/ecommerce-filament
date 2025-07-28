<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\InvoiceItem; // Assuming you have an InvoiceItem model to track sales
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class BestSeller extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Best Seller';

    protected function getData(): array
    {
        // Get filters
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        // Query to get best-selling products
        $bestSellingProducts = Product::query()
            ->withSum([
                'invoiceItems' => function ($query) use ($startDate, $endDate) {
                    if ($startDate) {
                        $query->whereHas('invoice', function ($q) use ($startDate) {
                            $q->where('date', '>=', $startDate);
                        });
                    }
                    if ($endDate) {
                        $query->whereHas('invoice', function ($q) use ($endDate) {
                            $q->where('date', '<=', $endDate);
                        });
                    }
                }
            ], 'quantity')
            ->orderByDesc('invoice_items_sum_quantity')
            ->take(5)
            ->get();

        // Prepare data for the chart
        $data = $bestSellingProducts->pluck('invoice_items_sum_quantity')->toArray();
        $labels = $bestSellingProducts->pluck('name')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Best Seller',
                    'data' => $data,
                    'backgroundColor' => ['#3490dc', '#ffed4a', '#e3342f', '#ff99ff', '#fdfd96'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
