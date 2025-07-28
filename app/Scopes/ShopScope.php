<?php

namespace App\Scopes;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Skip if no tenant or if model is User (since it uses pivot)
        if (!Filament::getTenant() || $model instanceof \App\Models\User) {
            return;
        }

        // Only apply if the model has a `shop_id` column
        if ($this->modelHasShopId($model)) {
            $builder->where('shop_id', Filament::getTenant()->id);
        }
    }

    protected function modelHasShopId(Model $model): bool
    {
        // Check if the table has `shop_id` column (cache-friendly)
        return in_array('shop_id', $model->getFillable());
    }
}