<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Exception;

abstract class Relationship
{
    protected ?string $source = null;

    abstract public function isReady(): bool;

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): string
    {
        if (null === $this->source) {
            throw new Exception('Cannot get source before setting'); // @codeCoverageIgnore
        }

        return $this->source;
    }
}
