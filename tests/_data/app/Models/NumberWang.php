<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table(charset: 'utf8')]
class NumberWang extends Model
{
    #[Column(type: 'tinyInteger', unsigned: true, autoIncrement: true)]
    public int $tiny;

    #[Column(type: 'smallInteger', unsigned: true, autoIncrement: true)]
    public int $small;

    #[Column(type: 'mediumInteger', unsigned: true, autoIncrement: true)]
    public int $medium;

    #[Column(unsigned: true, autoIncrement: true)]
    public int $regular;

    #[Column(nullable: true, unsigned: true, default: 123456789000.111111111111, comment: 'That\'s numberang!!!!!!!!')]
    public float $other;

    #[Column(length: 100)]
    public string $remember_token;

    #[Column(type: 'timestampTz', nullable: true)]
    public $deleted_at;

    #[Column]
    public string $none;
}
