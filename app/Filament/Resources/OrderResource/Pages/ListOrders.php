<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending'))
                ->badge(Order::query()->where('status', 'pending')->count()),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'processing'))
                ->badge(Order::query()->where('status', 'processing')->count()),
            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'shipped'))
                ->badge(Order::query()->where('status', 'shipped')->count()),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'delivered'))
                ->badge(Order::query()->where('status', 'delivered')->count()),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'cancelled'))
                ->badge(Order::query()->where('status', 'cancelled')->count()),
            'pending_payments' => Tab::make('Pending Payments')
                ->modifyQueryUsing(fn(Builder $query) =>
                    $query
                        ->where('payment_status', 'pending')
                        ->where('payment_method', 'manual_payment'))
                ->badge(Order::query()->where('payment_status', 'pending')->where('payment_method', 'manual_payment')->count())
                ->badgeColor('warning'),
        ];
    }
}