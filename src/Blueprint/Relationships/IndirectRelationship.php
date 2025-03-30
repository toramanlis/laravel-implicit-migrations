<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Toramanlis\ImplicitMigrations\Attributes\PivotColumn;
use Toramanlis\ImplicitMigrations\Attributes\PivotTable;
use Toramanlis\ImplicitMigrations\Attributes\Exception;

/** @package Toramanlis\ImplicitMigrations\Blueprint\Relationships */
class IndirectRelationship extends Relationship
{
    public readonly PivotTable $pivotTableAttribute;

    /** @var array<PivotColumn> */
    public readonly array $pivotColumnAttributes;

    /**
     * @param null|string $pivotTable
     * @param array<string> $relatedTables
     * @param array<string,string> $foreignKeys
     * @param array<string,string> $localKeys
     * @param array<string> $pivotColumns
     */
    public function __construct(
        protected ?string $pivotTable = null,
        protected array $relatedTables = [],
        protected array $foreignKeys = [],
        protected array $localKeys = [],
        protected array $pivotColumns = []
    ) {
    }

    public function isReady(): bool
    {
        return null !== $this->pivotTable
            && !empty($this->relatedTables)
            && !empty($this->foreignKeys)
            && !empty($this->localKeys);
    }

    /**
     * @param string $pivotTable
     * @return static
     */
    public function setPivotTable(string $pivotTable): static
    {
        $this->pivotTable = $pivotTable;
        return $this;
    }

    /**
     * @param array<string> $relatedTables
     * @return static
     */
    public function setRelatedTables(array $relatedTables): static
    {
        $this->relatedTables = $relatedTables;
        return $this;
    }

    /**
     * @param string $relatedTable
     * @return static
     */
    public function addRelatedTable(string $relatedTable): static
    {
        $this->relatedTables[] = $relatedTable;
        return $this;
    }

    /**
     * @param array<string,string> $foreignKeys
     * @return static
     */
    public function setForeignKeys(array $foreignKeys): static
    {
        $this->foreignKeys = $foreignKeys;
        return $this;
    }

    /**
     * @param string $relatedTable
     * @param string $foreignKey
     * @return static
     */
    public function addForeignKey(string $relatedTable, string $foreignKey): static
    {
        $this->foreignKeys[$relatedTable] = $foreignKey;
        return $this;
    }

    /**
     * @param array<string,string> $localKeys
     * @return static
     */
    public function setLocalKeys(array $localKeys): static
    {
        $this->localKeys = $localKeys;
        return $this;
    }

    /**
     * @param string $tableName
     * @param string $localKey
     * @return static
     */
    public function addLocalKey(string $tableName, string $localKey): static
    {
        $this->localKeys[$tableName] = $localKey;
        return $this;
    }

    /**
     * @param array<string> $pivotColumns
     * @return static
     */
    public function setPivotColumns(array $pivotColumns): static
    {
        $this->pivotColumns = $pivotColumns;
        return $this;
    }

    /**
     * @param string $pivotColumn
     * @return static
     */
    public function addPivotColumn(string $pivotColumn): static
    {
        $this->pivotColumns[] = $pivotColumn;
        return $this;
    }

    public function setPivotTableAttribute(PivotTable $attribute)
    {
        $this->pivotTableAttribute = $attribute;
        $attribute->name ??= $this->pivotTable;
        $this->pivotTable = $attribute->name;
    }

    /**
     * @param array<PivotColumn> $pivotColumnAttributes
     * @return static
     */
    public function setPivotColumnAttributes(array $pivotColumnAttributes): static
    {
        $this->pivotColumnAttributes = $pivotColumnAttributes;
        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPivotTable(): string
    {
        if (null === $this->pivotTable) {
            throw new Exception(Exception::CODE_RL_NO_PIVOT);
        }

        return $this->pivotTable;
    }

    /** @return array<string>  */
    public function getRelatedTables(): array
    {
        return $this->relatedTables;
    }

    /** @return array<string,string>  */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /** @return array<string,string>  */
    public function getLocalKeys(): array
    {
        return $this->localKeys;
    }

    /**
     * @return array<string>
     */
    public function getPivotColumns(): array
    {
        return $this->pivotColumns;
    }

    public function getForeignKeyAliases(): array
    {
        return $this->foreignKeys;
    }
}
