<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\EInvoiceService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Blade::if('einvoice', function () {
            return app(EInvoiceService::class)->isEnabled();
        });

        Blade::if('noeinvoice', function () {
            return !app(EInvoiceService::class)->isEnabled();
        });
        
    }
}
