<?php

namespace NanoRouter\Result;

class RouterResult
{

    /**
     * Fully qualified class name
     */
    private string $controller_name;
    private PathResult $path_result;
    private QueryResult $query_result;

    /**
     * @param string $controller_name Fully qualified class name
     * @param PathResult $path_result Matched patterns from the path
     * @param QueryResult $query_result QueryResult
     */
    public function __construct(string $controller_name,
        PathResult $path_result, QueryResult $query_result)
    {

        $this->controller_name = $controller_name;
        $this->path_result = $path_result;
        $this->query_result = $query_result;
    }

    /**
     * Gets the fully qualified class name of the controller
     */
    public function getControllerName(): string
    {
        return $this->controller_name;
    }

    public function getQueryResult(): QueryResult
    {
        return $this->query_result;
    }

    public function getPathResult(): PathResult
    {
        return $this->path_result;
    }
}
