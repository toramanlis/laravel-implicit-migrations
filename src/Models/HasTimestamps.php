<?php

namespace Toramanlis\ImplicitMigrations\Models;

use Doctrine\ORM\Mapping as ORM;

trait HasTimestamps
{
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    public $created_at;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    public $updated_at;
}
