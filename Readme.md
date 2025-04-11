[![codecov](https://codecov.io/gh/toramanlis/laravel-implicit-migrations/graph/badge.svg?token=BH5VBNIWMI)](https://codecov.io/gh/toramanlis/laravel-implicit-migrations)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/toramanlis/laravel-implicit-migrations.svg?style=flat-square)](https://packagist.org/packages/toramanlis/laravel-implicit-migrations)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/toramanlis/laravel-implicit-migrations.svg?style=flat-square)](https://packagist.org/packages/toramanlis/laravel-implicit-migrations)

![Laravel Implicit Migrations](https://repository-images.githubusercontent.com/853000736/e44bfe61-b6ff-46cb-87f8-0c5b67e6c438)

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
    - [`database.implications.<implication_name_in_snake_case>`](#databaseimplicationsimplication_name_in_snake_case)
- [Manual Migrations](#manual-migrations)
- [Implication Reference](#implication-reference)
    - [`Table`](#table)
    - [`Column`](#column)
    - [`Binary`](#binary)
    - [`Char`](#char)
    - [`CString`](#cstring "`String` is a reserved word in PHP")
    - [`Integer`](#integer)
    - [`TinyInteger`](#tinyinteger)
    - [`SmallInteger`](#smallinteger)
    - [`MedumInteger`](#mediuminteger)
    - [`BigInteger`](#biginteger)
    - [`Increments`](#increments)
    - [`TinyIncrements`](#tinyincrements)
    - [`SmallIncrements`](#smallincrements)
    - [`MedumIncrements`](#mediumincrements)
    - [`CFloat`](#cfloat "`Float` is a reserved word in PHP")
    - [`Decimal`](#decimal)
    - [`DateTime`](#datetime)
    - [`DateTimeTz`](#datetimetz)
    - [`Time`](#time)
    - [`TimeTz`](#timetz)
    - [`Timestamp`](#timestamp)
    - [`TimestampTz`](#timestamptz)
    - [`Enum`](#enum)
    - [`Set`](#set)
    - [`Geometry`](#geometry)
    - [`Geography`](#geography)
    - [`Computed`](#computed)
    - [`Index`](#index)
    - [`Unique`](#unique)
    - [`Primary`](#primary)
    - [`Relationship`](#relationship)
    - [`ForeignKey`](#foreignkey)
    - [`PivotTable`](#pivottable)
    - [`PivotColumn`](#pivotcolumn)
    - [`Off`](#off)


# Overview

## What It Is

This package is a tool that creates Laravel migration files by inspecting the application's models with the command `php artisan implicit-migrations:generate`. Even after you change the model classes, you can run the command and generate a migration with the necessary update operations.

## How It Works

With the most basic configuration, the `implicit-migrations:generate` artisan command looks at a Eloquent model and finds necessary information about the table properties such as the table name, primary key etc. Then it goes over the properties of the model and collects the name, type and default value information if provided. With the information collected, it creates a migration file and populates the `up()` and `down()` methods with the appropriate definitions.

### Implications

For further details, the generator refers to some additional data in the model class which we call "Implications". These can be specified with either annotations or attributes on the class, its properties and methods.

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

You can find out more on other implications in the [Implication Reference](#implication-reference) section.


#### PHP Attributes

Another way of specifying implications is using PHP attributes. The very same implications are avaliable as attributes with the same notation. This is the same model definition as above as far as the generator is concerned:

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

The obvious approach to tackling this is just adding the `implicit-migrations` package to the `require` instead of `require-dev`. The neat approach, on the other hand, is to get the attribute classes to the `database/migrations/attributes` directory of the project by publishing them with the `php artisan vendor:publish --tag=implication-attributes` command and add `"database/attributes/composer.json"` to the `composer.json` file like this:

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

This way, you can have the package in the `require-dev` section of your `composer.json` and still have the attribute classes available in production.

### Updates

This tool doesn't only work for creating a table for a model. If you change your model and run `implicit-migrations:generate` again, it will resolve the changes  by referring to the already generated migrations (**Only** the generated migrations that is. [See: Manual Migrations](#manual-migrations)) and generate a new migration that applies the changes to the table structure.

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

The recommended installation is using `composer require --dev toramanlis/laravel-implicit-migrations` command. Since **this will make the implication attributes unavailable in production**, if you want to use attributes for implications you have to take one of the following approaches:

### Publishing The Attributes

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

### Opting Out From Attributes

Each and every one of the implications are available as both attributes and annotations. You can completely give up using attributes and switch to the annotation notation with no missing functionality.


### Installing To Production

Alternatively, you can always install the package with `composer install toramanlis/laravel-implicit-migrations` without the `--dev` option. Having a tool like this in production sure is unnecessary, but it's just that, unnecessary.


# Configuration

## `database.model_paths`
##### Type: *`array`*
##### Default: *`['app/Models']`*

An `array` of paths relative to the project directory where application models reside. If there are multiple model and migration paths in a project, the migration files are created in the migration path that is closest to the source model in the directory tree (complicit with [nWidart/laravel-modules](https://github.com/nWidart/laravel-modules)).

## `database.auto_infer_migrations`
##### Type: *`bool`*
##### Default: *`true`*

This is a `boolean` value that controls, you guessed it, whether or not to infer the migration information automatically. What this means is basically, unless specified otherwise with an implication, none of the models, properties or methods are going to be inspected for migration information. However, if a property or method of a model has an implication, that model will be inspected. The default is `true`.

## `database.implications.<implication_name_in_snake_case>`
##### Type: *`bool`*
##### Default: *`true`*

These are `boolean` values that can be used to enable or disable each implication. The implication names have to be in snake case as per Laravel's convention for configuration keys e.g. `database.implications.foreign_key`. This set to `true` by default for all the implications.


# Manual Migrations

It's always a good idea to have a backup plan. You might come accross some more intricate or complicated requirements from a migration. For this reason, this tool doesn't take into account any migrations that does not have a `getSource()` method. This way, you can add your own custom migrations that are processed by Laravel's `migrate` command, but completely invisible to `implicit-migrations:generate`.

If a manual migration happens to have a method named `getSource`, the [Off](#off) implication can be utilized to indicate that it is in fact a manual migration.


# Implication Reference


All the PHP attributes for the implications reside in the namespace `Toramanlis\ImplicitMigrations\Attributes`. If you choose to utilize them, make sure they're available in your production environment as well. See the [installation section](#installation) for details.

Generally, the parameters of the implications are optional as they often have default values or can possibly be inferred from the rest of the information available in the application, such as the native PHP definitions of models, properties and methods or other implications' details.

Best to keep in mind that these details still might not be sufficient to make a definition and some of the *optional* parameters might, in fact, be required.

## `Table`
##### Target: *`class`*

`Table(?string $name = null, ?string $engine = null, ?string $charset = null, ?string $collation = null)`

Used with classes for specifying the table details. When the `database.auto_infer_migrations` configuration option is set to `true`, using this implication lets the class get processed.


## `Column`

##### Target: *`class`*, *`property`*

`Column(?string $type = null, ?string $name = null, ?bool $nullable = null, $default = null, ?int $length = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?int $precision = null, ?int $total = null, ?int $places = null, ?array $allowed = null, ?bool $fixed = null, ?string $subtype = null, ?int $srid = null, ?string $expression = null, ?string $collation = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Can be used both on classes and properties to define columns. The `name` parameter is mandatory when used on classes as it won't be able to infer the column name. In contrast, when used on a property, column name defaults to the name of the property. Either by using it on a property or providing a `name` that matches a property allows it to infer whatever information available in the definition of said property.


## `Binary`

##### Target: *`class`*, *`property`*

`Binary(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $fixed = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('binary', $name, $nullable, $default, fixed: $fixed, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Char`

##### Target: *`class`*, *`property`*

`Char(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $length = null, ?string $collation = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('char', $name, $nullable, $default, $length, collation: $collation, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## [`CString`](## "`String` is a reserved wordin PHP")

##### Target: *`class`*, *`property`*

`CString(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $length = null, ?string $collation = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('string', $name, $nullable, $default, $length, collation: $collation, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Integer`

##### Target: *`class`*, *`property`*

`Integer(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('integer', $name, $nullable, $default, unsigned: $unsigned, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `TinyInteger`

##### Target: *`class`*, *`property`*

`TinyInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('tinyInteger', $name, $nullable, $default, unsigned: $unsigned, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `SmallInteger`

##### Target: *`class`*, *`property`*

`SmallInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('smallInteger', $name, $nullable, $default, unsigned: $unsigned, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `MediumInteger`

##### Target: *`class`*, *`property`*

`MediumInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('mediumInteger', $name, $nullable, $default, unsigned: $unsigned, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `BigInteger`

##### Target: *`class`*, *`property`*

`BigInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('bigInteger', $name, $nullable, $default, unsigned: $unsigned, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `UnsignedInteger`

##### Target: *`class`*, *`property`*

`UnsignedInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('unsignedInteger', $name, $nullable, $default, unsigned: true, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `UnsignedTinyInteger`

##### Target: *`class`*, *`property`*

`UnsignedTinyInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('unsignedTinyInteger', $name, $nullable, $default, unsigned: true, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `UnsignedSmallInteger`

##### Target: *`class`*, *`property`*

`UnsignedSmallInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('unsignedSmallInteger', $name, $nullable, $default, unsigned: true, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `UnsignedMediumInteger`

##### Target: *`class`*, *`property`*

`UnsignedMediumInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('unsignedMediumInteger', $name, $nullable, $default, unsigned: true, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `UnsignedBigInteger`

##### Target: *`class`*, *`property`*

`UnsignedBigInteger(protected ?string $name = null, ?bool $nullable = null, $default = null, ?bool $autoIncrement = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('unsignedBigInteger', $name, $nullable, $default, unsigned: true, autoIncrement: $autoIncrement, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Increments`

##### Target: *`class`*, *`property`*

`Increments(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('increments', $name, $nullable, $default, unsigned: true, autoIncrement: true, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `TinyIncrements`

##### Target: *`class`*, *`property`*

`TinyIncrements(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('tinyIncrements', $name, $nullable, $default, unsigned: true, autoIncrement: true, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `SmallIncrements`

##### Target: *`class`*, *`property`*

`SmallIncrements(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('smallIncrements', $name, $nullable, $default, unsigned: true, autoIncrement: true, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `MediumIncrements`

##### Target: *`class`*, *`property`*

`MediumIncrements(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('mediumIncrements', $name, $nullable, $default, unsigned: true, autoIncrement: true, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `BigIncrements`

##### Target: *`class`*, *`property`*

`BigIncrements(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('bigIncrements', $name, $nullable, $default, unsigned: true, autoIncrement: true, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## [`CFloat`](## "`Float` is a reserved wordin PHP")

##### Target: *`class`*, *`property`*

`CFloat(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('float', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Decimal`

##### Target: *`class`*, *`property`*

`Decimal(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $total = null, ?int $places = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('decimal', $name, $nullable, $default, total: $total, places: $places, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `DateTime`

##### Target: *`class`*, *`property`*

`DateTime(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('dateTime', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `DateTimeTz`

##### Target: *`class`*, *`property`*

`DateTimeTz(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('dateTimeTz', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Time`

##### Target: *`class`*, *`property`*

`Time(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('time', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `TimeTz`

##### Target: *`class`*, *`property`*

`TimeTz(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('timeTz', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Timestamp`

##### Target: *`class`*, *`property`*

`Timestamp(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('timestamp', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `TimestampTz`

##### Target: *`class`*, *`property`*

`TimestampTz(protected ?string $name = null, ?bool $nullable = null, $default = null, ?int $precision = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('timestampTz', $name, $nullable, $default, precision: $precision, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Enum`

##### Target: *`class`*, *`property`*

`Enum(protected ?string $name = null, ?bool $nullable = null, $default = null, ?array $allowed = null, ?string $collation = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('enum', $name, $nullable, $default, allowed: $allowed, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Set`

##### Target: *`class`*, *`property`*

`Set(protected ?string $name = null, ?bool $nullable = null, $default = null, ?array $allowed = null, ?string $collation = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('set', $name, $nullable, $default, allowed: $allowed, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Geometry`

##### Target: *`class`*, *`property`*

`Geometry(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $subtype = null, ?int $srid = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('geometry', $name, $nullable, $default, subtype: $subtype, srid: $srid, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Geography`

##### Target: *`class`*, *`property`*

`Geography(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $subtype = null, ?int $srid = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('geography', $name, $nullable, $default, subtype: $subtype, srid: $srid, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Computed`

##### Target: *`class`*, *`property`*

`Computed(protected ?string $name = null, ?bool $nullable = null, $default = null, ?string $expression = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Alias for `Column('computed', $name, $nullable, $default, expression: $expression, comment: $comment, virtualAs: $virtualAs, storedAs: $storedAs, after: $after)`


## `Index`

##### Target: *`class`*, *`property`*

`Index(null|array|string $column = null, string $type = 'index', ?string $name = null, ?string $algorithm = null, ?string $language = null)`

Just like `Column`, this can also be used both on classes and properties. The `column` parameter is optional when used on a property and defaults to the column name associated with that property even if the property doesn't have a `Column` implication of its own. When used on a class, the `column` parameter is mandatory.

When `Index` is associated with a single column name by either using it on a property or having given a value to the `column` parameter, it will try and ensure the existence of a column with that name, using any information available in the model definition.


## `Unique`

##### Target: *`class`*, *`property`*

`Unique(null|array|string $column = null, ?string $name = null, ?string $algorithm = null, ?string $language = null)`

Alias for `Index($column, type: 'unique', ...$args)`


## `Primary`

##### Target: *`class`*, *`property`*

`Primary(null|array|string $column = null, ?string $name = null, ?string $algorithm = null, ?string $language = null)`

Alias for `Index($column, type: 'primary', ...$args)`


## `Relationship`

##### Target: *`method`*

`Relationship()`

Specifies that a method is a Laravel relationship. What kind of relationship it is will always be inferred by the return type of the method. This implication is redundant if the `database.auto_infer_migrations` configuration option is set to `true`, as the return type of a `public` method is already taken as an implication of whether or not it's a relationship method.

If the type of relationship requires tables and columns that are not defined, `Relationship` will try to ensure them in the migration using whatever information is available.


## `ForeignKey`

##### Target: *`class`*, *`property`*

`ForeignKey(string $on, null|array|string $column = null, null|array|string $references = null, ?string $onUpdate = null, ?string $onDelete = null)`

Similar to `Index`, this can be used both on classes and properties, but with classes, it's mandatory to provide the `column` parameter.

The `on` parameter can be a table name or a class name of a model.


## `PivotTable`

##### Target: *`method`*

`PivotTable(?string $name = null, ?string $engine = null, ?string $charset = null, ?string $collation = null)`

Specifies the details of a pivot table of a relationship. Even if no `Relationship` implication is present, having this implication lets the generator know it's a relationship method.


## `PivotColumn`

##### Target: *`method`*

`PivotColumn(?string $name, protected ?string $type = null, ?bool $nullable = null, $default = null, ?int $length = null, ?bool $unsigned = null, ?bool $autoIncrement = null, ?int $precision = null, ?int $total = null, ?int $places = null, ?array $allowed = null, ?bool $fixed = null, ?string $subtype = null, ?int $srid = null, ?string $expression = null, ?string $collation = null, ?string $comment = null, ?string $virtualAs = null, ?string $storedAs = null, ?string $after = null)`

Defines a column on a pivot table of a relationship. Just like [`PivotTable`](#pivottable), having this implication lets the generator know it's a relationship method. Since pivot tables typically don't have models of their own, we define any **extra** columns on the relationship method they are required by. Only the columns other than the foreign keys need this implicaiton, foreign keys are already covered with the relationship. It's still allowed to use this implication to fine tune them, though.


## `Off`

##### Target: *`class`*, *`property`*, *`method`*

`Off()`

Lets the generator know that the given class, property or method should be ignored. This includes `getSource` method a migration. If you have a manually written migration that happens to have a method named `getSource`, you can add this implication to that method to keep the generator off of that migration.
