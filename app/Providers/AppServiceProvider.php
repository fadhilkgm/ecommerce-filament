<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Permission;
use App\Scopes\ShopScope;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {

        // Get all models that extend Illuminate\Database\Eloquent\Model
        $models = $this->getApplicationModels();

        foreach ($models as $model) {
            // Skip User model (handled separately via pivot)
            if ($model === User::class) {
                continue;
            }

            // Check if model has shop_id column
            if (Schema::hasColumn((new $model)->getTable(), 'shop_id')) {
                $model::addGlobalScope(new ShopScope);
            }
        }
    }

    protected function getApplicationModels()
    {
        $models = [];
        $path = app_path('Models');

        foreach (scandir($path) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $class = 'App\\Models\\' . str_replace('.php', '', $file);

            if (is_subclass_of($class, Model::class)) {
                $models[] = $class;
            }
        }

        return $models;
    }
}
