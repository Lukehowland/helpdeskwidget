<?php

declare(strict_types=1);

namespace Lukehowland\HelpdeskWidget;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Lukehowland\HelpdeskWidget\Console\Commands\InstallCommand;
use Lukehowland\HelpdeskWidget\View\Components\HelpdeskWidget;

class HelpdeskWidgetServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/helpdeskwidget.php',
            'helpdeskwidget'
        );

        // Register the main service
        $this->app->singleton(HelpdeskService::class, function ($app) {
            return new HelpdeskService(
                config('helpdeskwidget.api_url'),
                config('helpdeskwidget.api_key')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ================================================================
        // PUBLISH CONFIG
        // ================================================================
        $this->publishes([
            __DIR__ . '/../config/helpdeskwidget.php' => config_path('helpdeskwidget.php'),
        ], 'helpdeskwidget-config');

        // ================================================================
        // LOAD VIEWS
        // ================================================================
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'helpdeskwidget');

        // Publish views (optional)
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/helpdeskwidget'),
        ], 'helpdeskwidget-views');

        // ================================================================
        // REGISTER BLADE COMPONENT
        // ================================================================
        Blade::component('helpdesk-widget', HelpdeskWidget::class);

        // ================================================================
        // REGISTER COMMANDS (only in console)
        // ================================================================
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
