<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Illuminate\Database\Schema\Blueprint;

trait Applicable
{
    abstract public static function enabled(): bool;

    abstract protected function process(Blueprint $table): Blueprint;

    public function apply(Blueprint $table): Blueprint
    {
        if (!$this->enabled()) {
            return $table;
        }

        return $this->process($table);
    }
}
