<?php

namespace NanoRouter\Result;

use NanoRouter\Exception\ResultException;

abstract class RequestResult
{

    /**
     * @var array Matched URL parameters
     */
    protected $matches = [];

    public function hasString(string $name): bool
    {
        return isset($this->matches[$name]) && is_string($this->matches[$name]);
    }

    public function getString(string $name): string
    {
        if (!$this->hasString($name)) {
            throw new ResultException("No string mathches for {$name}");
        }
        return $this->matches[$name];
    }

    public function hasInteger(string $name): bool
    {
        return isset($this->matches[$name]) && is_numeric($this->matches[$name]);
    }

    public function getInteger(string $name): int
    {
        if (!$this->hasInteger($name)) {
            throw new ResultException("No integer matches for {$name}");
        }

        return (int) $this->matches[$name];
    }
}
