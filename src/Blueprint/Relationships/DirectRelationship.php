<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;

/** @package Toramanlis\ImplicitMigrations\Blueprint\Relationships */
class DirectRelationship extends Relationship
{
    public function __construct(
        protected ?string $parentTable = null,
        protected ?string $relatedTable = null,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null
    ) {
    }

    public function isReady(): bool
    {
        return null !== $this->parentTable
            && null !== $this->relatedTable
            && null !== $this->foreignKey
            && null !== $this->localKey;
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
            throw new ImplicationException(ImplicationException::CODE_RL_NO_PARENT);
        }

        return $this->parentTable;
    }

    public function getRelatedTable(): string
    {
        if (null === $this->relatedTable) {
            throw new ImplicationException(ImplicationException::CODE_RL_NO_RELATED);
        }

        return $this->relatedTable;
    }

    public function getForeignKey(): string
    {
        if (null === $this->foreignKey) {
            throw new ImplicationException(ImplicationException::CODE_RL_NO_FOREIGN);
        }

        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        if (null === $this->localKey) {
            throw new ImplicationException(ImplicationException::CODE_RL_NO_LOCAL);
        }

        return $this->localKey;
    }

    public function getForeignKeyAlias(): string
    {
        return $this->getForeignKey();
    }
}
