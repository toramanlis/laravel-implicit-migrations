<?php

namespace Toramanlis\ImplicitMigrations\Providers;

use Illuminate\Support\Fluent;
use Illuminate\Support\ServiceProvider;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\IndexExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\DirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\IndirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\MorphicDirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\MorphicIndirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Toramanlis\ImplicitMigrations\Console\Commands\GenerateMigrationCommand;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;
use Toramanlis\ImplicitMigrations\Generator\TemplateManager;

class ImplicitMigrationsServiceProvider extends ServiceProvider
{
    public $bindings = [
        MigrationGenerator::class,
        SimplifyingBlueprint::class,
        Column::class,
        BlueprintDiff::class,
        Fluent::class,
        TemplateManager::class,
        Manager::class,
        ColumnDiffExporter::class,
        ColumnExporter::class,
        IndexExporter::class,
        TableDiffExporter::class,
        TableExporter::class,
        DirectRelationship::class,
        IndirectRelationship::class,
        MorphicDirectRelationship::class,
        MorphicIndirectRelationship::class,
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
        ], 'implication-attributes');
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
