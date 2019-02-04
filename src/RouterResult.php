<?php
namespace NaiveRouter;

class RouterResult
{

    /**
     * @var string Class name
     */
    private $controller;

    /**
     * @var array Matched URL parameters
     */
    private $matches;

    public function __construct(string $controller, array $matches)
    {

        $this->controller = $controller;
        $this->matches = $matches;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function hasString(string $name): bool
    {
        return isset($this->matches[$name]);
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

    public function getInetger(string $name): int
    {
        if (!$this->hasInteger($name)) {
            throw new ResultException("No integer matches for {$name}");
        }

        return (int) $this->matches[$name];
    }
}
