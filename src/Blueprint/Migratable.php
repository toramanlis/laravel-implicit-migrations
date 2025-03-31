<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Illuminate\Support\Fluent;

interface Migratable
{
    public function getDependedColumnNames(): array;

    public function getAddedColumnNames(): array;

    public function extractForeignKey(string $on, string $reference): Fluent;
}
