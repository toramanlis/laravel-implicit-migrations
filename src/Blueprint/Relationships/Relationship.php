<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Exception;

abstract class Relationship
{
    protected ?string $source = null;

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): string
    {
        if (null === $this->source) {
            throw new Exception('Cannot get source before setting');
        }

        return $this->source;
    }
}
