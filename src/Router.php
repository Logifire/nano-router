<?php
namespace NanoRouter;

use NanoRouter\Exception\RouterException;
use NanoRouter\Result\PathResult;
use NanoRouter\Result\QueryResult;
use NanoRouter\Result\RouterResult;
use Psr\Http\Message\ServerRequestInterface;

class Router
{

    /**
     * @var RouterCore
     */
    private $router_core;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const MEHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_HEAD = 'HEAD';

    public function __construct(RouterCore $router_core)
    {
        $this->router_core = $router_core;
    }

    /**
     * @param string $method HTTP method e.g. GET. See self::METHOD_* constants.
     * @param string $path Request path e.g. /profiles/(?<uuid>[0-9a-f\-]{36})
     * @param string $controller_name The fully qualified class name
     * 
     * @return void
     * 
     * @throws RouterException If path is already configured, or unsupported method
     */
    public function configurePath(string $method, string $path, string $controller_name): void
    {
        $this->router_core->configurePath($method, $path, $controller_name);
    }

    /**
     * @param ServerRequestInterface $request
     * @return RouterResult|null
     */
    public function processRequest(ServerRequestInterface $request): ?RouterResult
    {
        $router_result = null;
        $method = $request->getMethod();
        $uri = $request->getUri();
        $requested_path = $uri->getPath();
        $query = $uri->getQuery();

        $lookup_result = $this->router_core->lookup($method, $requested_path);

        ["controller_name" => $controller_name, "group_name" => $path_keys] = $lookup_result;

        if ($controller_name) {
            $path_result = new PathResult($path_keys);
            $query_result = new QueryResult($query);
            $router_result = new RouterResult($controller_name, $path_result, $query_result);
        }
        return $router_result;
    }
}
