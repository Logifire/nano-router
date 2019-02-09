<?php
namespace NaiveRouter;

class RouterResult extends Result
{

    /**
     * @var QueryResult
     */
    private $query_result;

    /**
     * @var string Class name
     */
    private $controller;

    /**
     * @param string $controller Controller class
     * @param array $matches Matched patterns from the path
     * @param Queryresult $query_result QueryResult 
     */
    public function __construct(string $controller, array $matches, QueryResult $query_result)
    {

        $this->controller = $controller;
        $this->matches = $matches;
        $this->query_result = $query_result;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getQueryResult(): QueryResult
    {
        return $this->query_result;
    }
}
