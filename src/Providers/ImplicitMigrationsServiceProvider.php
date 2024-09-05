<?php

namespace Toramanlis\ImplicitMigrations\Providers;

use Illuminate\Support\ServiceProvider;
use Toramanlis\ImplicitMigrations\Console\Commands\GenerateMigrationCommand;

class ImplicitMigrationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateMigrationCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/database.php', 'database');
    }
}
