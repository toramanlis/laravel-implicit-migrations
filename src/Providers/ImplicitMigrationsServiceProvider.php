<?php

namespace Toramanlis\ImplicitMigrations\Providers;

use Illuminate\Support\ServiceProvider;
use Toramanlis\ImplicitMigrations\Console\Commands\GenerateMigrationCommand;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;

class ImplicitMigrationsServiceProvider extends ServiceProvider
{
    public $bindings = [
        MigrationGenerator::class,
    ];

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateMigrationCommand::class,
            ]);
        }

        $this->publishes([
            implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Attributes']) => database_path('attributes'),
        ], 'attributes');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            'config',
            'database.php'
        ]), 'database');
    }
}
