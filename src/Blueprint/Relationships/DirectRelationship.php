<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Exception;

/** @package Toramanlis\ImplicitMigrations\Blueprint\Relationships */
class DirectRelationship extends Relationship
{
    /**
     * @param null|string $parentTable
     * @param null|string $relatedTable
     * @param null|string $foreignKey
     * @param null|string $localKey
     */
    public function __construct(
        protected ?string $parentTable = null,
        protected ?string $relatedTable = null,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null
    ) {
    }

    public function setParentTable(string $parentTable): static
    {
        $this->parentTable = $parentTable;
        return $this;
    }

    public function setRelatedTable(string $relatedTable): static
    {
        $this->relatedTable = $relatedTable;
        return $this;
    }

    public function setForeignKey(string $foreignKey): static
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function setLocalKey(string $localKey): static
    {
        $this->localKey = $localKey;
        return $this;
    }

    public function getParentTable(): string
    {
        if (null === $this->parentTable) {
            throw new Exception('Unable to get parent table before setting');
        }

        return $this->parentTable;
    }

    public function getRelatedTable(): string
    {
        if (null === $this->relatedTable) {
            throw new Exception('Unable to get related table before setting');
        }

        return $this->relatedTable;
    }

    public function getForeignKey(): string
    {
        if (null === $this->foreignKey) {
            throw new Exception('Unable to get foreign key before setting');
        }

        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        if (null === $this->localKey) {
            throw new Exception('Unable to get local key before setting');
        }

        return $this->localKey;
    }
}
