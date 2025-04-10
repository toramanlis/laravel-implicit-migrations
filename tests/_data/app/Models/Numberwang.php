<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\BigIncrements;
use Toramanlis\ImplicitMigrations\Attributes\BigInteger;
use Toramanlis\ImplicitMigrations\Attributes\Binary;
use Toramanlis\ImplicitMigrations\Attributes\CFloat;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\Computed;
use Toramanlis\ImplicitMigrations\Attributes\CString;
use Toramanlis\ImplicitMigrations\Attributes\DateTime;
use Toramanlis\ImplicitMigrations\Attributes\DateTimeTz;
use Toramanlis\ImplicitMigrations\Attributes\Decimal;
use Toramanlis\ImplicitMigrations\Attributes\Enum;
use Toramanlis\ImplicitMigrations\Attributes\Geography;
use Toramanlis\ImplicitMigrations\Attributes\Geometry;
use Toramanlis\ImplicitMigrations\Attributes\Increments;
use Toramanlis\ImplicitMigrations\Attributes\Integer;
use Toramanlis\ImplicitMigrations\Attributes\MediumIncrements;
use Toramanlis\ImplicitMigrations\Attributes\MediumInteger;
use Toramanlis\ImplicitMigrations\Attributes\Set;
use Toramanlis\ImplicitMigrations\Attributes\SmallIncrements;
use Toramanlis\ImplicitMigrations\Attributes\SmallInteger;
use Toramanlis\ImplicitMigrations\Attributes\Table;
use Toramanlis\ImplicitMigrations\Attributes\Time;
use Toramanlis\ImplicitMigrations\Attributes\Timestamp;
use Toramanlis\ImplicitMigrations\Attributes\TimestampTz;
use Toramanlis\ImplicitMigrations\Attributes\TimeTz;
use Toramanlis\ImplicitMigrations\Attributes\TinyIncrements;
use Toramanlis\ImplicitMigrations\Attributes\TinyInteger;
use Toramanlis\ImplicitMigrations\Attributes\UnsignedBigInteger;
use Toramanlis\ImplicitMigrations\Attributes\UnsignedInteger;
use Toramanlis\ImplicitMigrations\Attributes\UnsignedMediumInteger;
use Toramanlis\ImplicitMigrations\Attributes\UnsignedSmallInteger;
use Toramanlis\ImplicitMigrations\Attributes\UnsignedTinyInteger;

#[Table(charset: 'utf8')]
class Numberwang extends Model
{
    #[TinyInteger]
    public int $tiny;

    #[SmallInteger]
    public int $small;

    #[MediumInteger]
    public int $medium;

    #[Integer]
    public int $regular;

    #[BigInteger]
    public int $big;

    #[UnsignedTinyInteger]
    public int $unsignedTiny;

    #[UnsignedSmallInteger]
    public int $unsignedSmall;

    #[UnsignedMediumInteger]
    public int $unsignedMedium;

    #[UnsignedInteger]
    public int $unsignedRegular;

    #[UnsignedBigInteger]
    public int $unsignedBig;

    #[TinyIncrements]
    public int $tinyIncrements;

    #[SmallIncrements]
    public int $smallIncrements;

    #[MediumIncrements]
    public int $mediumIncrements;

    #[Increments]
    public int $regularIncrements;

    #[BigIncrements]
    public int $bigIncrements;

    #[Column(unsigned: true, autoIncrement: true)]
    public int $normal;

    #[Column(nullable: true, unsigned: true, default: 123456789000.111111111111, comment: 'That\'s numberang!!!!!!!!')]
    public float $other;

    #[Column(length: 100)]
    public string $remember_token;

    #[Column(type: 'timestampTz', nullable: true)]
    public $deleted_at;

    #[Column]
    public string $none;

    #[Binary]
    public $binary;

    #[CFloat]
    public $float;

    #[Computed(expression: '5')]
    public $computed;

    #[CString(length: 12)]
    public $string;

    #[DateTime]
    public $dateTime;

    #[DateTimeTz]
    public $dateTimeTz;

    #[Decimal(total: 4)]
    public $decimal;

    #[Enum(allowed: ['a', 'b'])]
    public $enum;

    #[Geography(srid: 4325)]
    public $geography;

    #[Geometry]
    public $geometry;

    #[Set(allowed: ['a', 'b'])]
    public $set;

    #[Time]
    public $time;

    #[Timestamp]
    public $timestamp;

    #[TimeTz]
    public $timeTz;

    #[TimestampTz]
    public $timestampTz;
}
