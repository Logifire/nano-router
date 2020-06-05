<?php
namespace NanoRouter\Result;

class RouterResult
{

    /**
     * @var string Fully qualified class name
     */
    private $controller_name;

    /**
     * @var PathResult
     */
    private $path_result;

    /**
     * @param string $controller_name Fully qualified class name
     * @param PathResult $path_result Matched patterns from the path
     */
    public function __construct(string $controller_name, PathResult $path_result)
    {

        $this->controller_name = $controller_name;
        $this->path_result = $path_result;
    }

    /**
     * Gets the fully qualified class name of the controller
     */
    public function getControllerName(): string
    {
        return $this->controller_name;
    }

    public function getPathResult(): PathResult
    {
        return $this->path_result;
    }
}
