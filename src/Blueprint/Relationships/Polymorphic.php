<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

trait Polymorphic
{
    protected string $typeKey;

    public function setTypeKey(string $typeKey): static
    {
        $this->typeKey = $typeKey;
        return $this;
    }

    public function getTypeKey(): string
    {
        return $this->typeKey;
    }
}
