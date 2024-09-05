<?php

namespace Toramanlis\ImplicitMigrations\Models;

use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletes
{
    use EloquentSoftDeletes;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    public $deleted_at;
}
