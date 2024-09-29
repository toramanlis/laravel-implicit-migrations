<?php

namespace Toramanlis\ImplicitMigrations\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class ImplicitMigration extends Migration
{
    protected const TABLE_NAME = '';
    protected const TABLE_NAME_OLD = null;
    protected const TABLE_NAME_NEW = null;

    protected const MODE = null;

    protected const SOURCE = '';

    public const MODE_CREATE = 'create';
    public const MODE_UPDATE = 'update';

    abstract public function tableUp(Blueprint $table): void;

    abstract public function tableDown(Blueprint $table): void;

    public function getMode()
    {
        return static::MODE;
    }

    public function getTableNameOld()
    {
        return static::TABLE_NAME_OLD ?? static::TABLE_NAME;
    }

    public function getTableNameNew()
    {
        return static::TABLE_NAME_NEW ?? static::TABLE_NAME;
    }

    public function getSource()
    {
        return static::SOURCE;
    }

    public function up(): void
    {
        $method = static::MODE === self::MODE_CREATE ? 'create' : 'table';
        Schema::{$method}(static::TABLE_NAME_OLD, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::table(static::TABLE_NAME_NEW, function (Blueprint $table) {
            $this->tableDown($table);
        });
    }
}
