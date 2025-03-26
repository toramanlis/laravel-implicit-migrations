<?php

namespace Toramanlis\ImplicitMigrations\Generator;

enum MigrationMode: string
{
    case Create = 'create';

    case Update = 'update';
}
