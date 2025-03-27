[![codecov](https://codecov.io/gh/toramanlis/implicit-migrations/graph/badge.svg?token=BH5VBNIWMI)](https://codecov.io/gh/toramanlis/implicit-migrations)
[![Known Vulnerabilities](https://snyk.io/test/github/toramanlis/implicit-migrations/badge.svg)](https://snyk.io/test/github/toramanlis/implicit-migrations)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/toramanlis/implicit-migrations.svg?style=flat-square)](https://packagist.org/packages/toramanlis/implicit-migrations)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/toramanlis/implicit-migrations.svg?style=flat-square)](https://packagist.org/packages/toramanlis/implicit-migrations)


- [Overview](#overview)
    - [What It Is](#what-it-is)
    - [How It Works](#how-it-works)
        - [Implications](#implications)
            - [Annotations](#annotations)
            - [PHP Attributes](#php-attributes)
        - [Updates](#updates)
- [Installation](#installation)
    - [Publishing The Attributes](#publishing-the-attributes)
    - [Opting Out From Attributes](#opting-out-from-attributes)
    - [Installing To Production](#installing-to-production)
- [Configuration](#configuration)
    - [`database.model_paths`](#databasemodel_paths)
    - [`database.auto_infer_migrations`](#databaseauto_infer_migrations)
- [Manual Migrations](#manual-migrations)
- [Implication Reference](#implication-reference)


# Overview

## What It Is

This package is a tool that creates Laravel migration files by inspecting the application's models by the command `php artisan implicit-migrations:generate`. Even after you change the model classes, you can run the command to generate a migration with the necessary update operations.

## How It Works

With the most basic configuration, the `implicit-migrations:generate` artisan command looks at the Eloquent model and finds necessary information about the table properties such as the table name, primary key etc. Then it goes over the properties of the model and collects the name, type and default value information if provided. With the information collected, it creates a migration file and populates the `up()` and `down()` methods with the appropriate definitions.

### Implications

For further details, the generator refers to some additional data in the model class which we call "Implications". These can be specified with either annotations or attributes on the class, properties and methods.

#### Annotations

Annotations in DocBlocks with the format `@<implication-name>(<parameters>)` are recognized and interpreted as implications. For example, an annotation like this tells the generator that this integer property corresponds to an `UNSIGNED` `INT` column named `product_id` in the `order_items` table:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /**
     * @Column(unsigned: true)
     */
    public int $product_id;
}
```

In turn, the generator produces a migration like this:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\OrderItem as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'order_items';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->integer('product_id')->unsigned();
        $table->timestamps();
    }

    public function up(): void
    {
        Schema::create(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::drop(static::TABLE_NAME);
    }
};
```

You can find out more on other implications are available in the [Implication Reference](#implication-reference) section.


#### PHP Attributes

Another way of specifying implications is using PHP's attributes. The very same implications are avaliable as attributes with the same notation. This is the same model definition as above as far as the generator is concerned:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Column;

class OrderItem extends Model
{
    #[Column(unsigned: true)]
    public int $product_id;
}
```

The advantage of this option is that your IDE/Editor will recognize the attributes as the classes they are and provide autocompletion and descriptions. The down side is that the classes have to be referenced in the models and now they need to be existent in the production environment too.

The obvious approach to this is just adding the `implicit-migrations` package to the `require` instead of `require-dev`. The neat approach is to get the attribute classes to the `database/migrations/attributes` directory in the project by publishing them with the `php artisan vendor:publish --tag=implication-attributes` command and add `"database/attributes/composer.json"` to the `composer.json` file like this:

```json
...
"extra": {
    "merge-plugin": {
        "include": [
            "database/attributes/composer.json"
        ]
    }
}
...
```

This way, you can have the package in the `require` section of your `composer.json` and still have the attribute classes available in production.

### Updates

This tool doesn't only work for creating a table for a model. If you change your model and run `implicit-migrations:generate` again, it will resolve the changes in the model by referring to the already generated migrations (**Only** the generated migrations that is. [See: Manual Migrations](#manual-migrations)) and generate a new migration that applies the changes to the table structure.

For example if you update the above model and add another property to it:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    /**
     * @Column(unsigned: true)
     */
    public int $product_id;

    public int $order_id;
}
```

After you run `php artisan implicit-migrations:generate` having the initial migration above already in place, you will get another migration like this:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\OrderItem as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'order_items';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->integer('order_id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->dropColumn('order_id');
    }

    public function up(): void
    {
        Schema::table(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::table(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableDown($table);
        });
    }
};
```


# Installation

The recommended installation is using `composer require --dev toramanlis/implicit-migrations` command. Since **this will make the implication attributes unavailable in production**, if you want to use attributes for implications you have to take one of these approaches:

## Publishing The Attributes

You can publish the attribute classes with `php artisan vendor:publish --tag=implication-attributes` and add the line `"database/attributes/composer.json"` in the `composer.json` file like this:

```json
...
"extra": {
    "merge-plugin": {
        "include": [
            "database/attributes/composer.json"
        ]
    }
}
...
```

## Opting Out From Attributes

Each and every one of the implications are available as both attributes and annotations. You can completely give up using attributes and switch to the annotation notation with no missing functionality.


## Installing To Production

Alternatively, you can always install the package with `composer install toramanlis/implicit-migrations` without the `--dev` option. Having a tool like this in production sure is unnecessary, but it's just that, unnecessary.


# Configuration

There's very little configuration required for using this tool.

## `database.model_paths`

An `array` of paths relative to the project directory where application models reside. The default is a single path the same as Laravel' default model path: `['app/Models']`

## `database.auto_infer_migrations`

This is a `boolean` value that controls, you guessed it, whether or not to infer the migration information automatically. What this means is basically, unless specified otherwise with an implication, none of the models, properties or methods are going to be inspected for migration information. If a property or method of a model has an implication, that model will also be inspected. The default is `true`.


# Manual Migrations

It's always a good idea to have a backup plan. You might come accross some more intricate or complicated requirements from a migration. For this reason, this tool doesn't take into account any migrations that does not have a `getSource()` method. This way, you can add your own custom migrations that are processed with Laravel's `migrate` command, but completely invisible to `implicit-migrations:generate`.


# Implication Reference

## `Table`

*to be documented*

## `Column`

*to be documented*

## `Index`

*to be documented*

## `Unique`

*to be documented*

## `Primary`

*to be documented*

## `Relationship`

*to be documented*

## `ForeignKey`

*to be documented*

## `PivotColumn`

*to be documented*

## `Off`

*to be documented*