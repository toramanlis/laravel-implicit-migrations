<?php

namespace Toramanlis\ImplicitMigrations\Schemas;

use Doctrine\DBAL\Schema\Table;

class DroppableTable extends Table
{
    protected bool $dropped = false;

    public function __construct(Table $referenceTable)
    {
        $cloned = clone $referenceTable;
        parent::__construct(
            $cloned->getName(),
            $cloned->getColumns(),
            $cloned->getIndexes(),
            $cloned->getUniqueConstraints(),
            $cloned->getForeignKeys(),
            $cloned->getOptions(),
        );
    }

    public function drop()
    {
        $this->dropped = true;
    }

    public function isDropped(): bool
    {
        return $this->dropped;
    }
}
