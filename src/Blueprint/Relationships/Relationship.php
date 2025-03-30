<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

abstract class Relationship
{
    protected string $source ;

    abstract public function isReady(): bool;

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
