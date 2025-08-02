<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dash;
use App\Filament\Widgets\BestSeller;
use App\Filament\Widgets\FinancialFlowStats;
use App\Filament\Widgets\PaymentMethodStats;
use App\Filament\Widgets\SalesStats;
use App\Filament\Widgets\TotalSalesChart;
use App\Models\Shop;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Support\Colors\Color;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->tenant(Shop::class, 'slug')
            ->tenantMenu(false)
            ->brandName(fn()=> Filament::getTenant()?->name)
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->databaseTransactions()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dash::class,
            ])
            ->widgets([
                FinancialFlowStats::class,
                PaymentMethodStats::class,
                SalesStats::class,
                BestSeller::class,
                TotalSalesChart::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
