<?php
namespace NanoRouter;

class QueryResult extends RequestResult
{

    public function __construct(string $query)
    {
        parse_str($query, $this->matches);
    }

    public function hasCollection(string $name): bool
    {
        return isset($this->matches[$name]) && is_array($this->matches[$name]);
    }

    public function getCollection(string $name): array
    {
        if (!$this->hasCollection($name)) {
            throw new ResultException("No collection matches for {$name}");
        }

        return $this->matches[$name];
    }
}
