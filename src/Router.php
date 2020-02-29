<?php
namespace NanoRouter;

use NanoRouter\Exception\RouterException;
use Psr\Http\Message\ServerRequestInterface;

class Router
{

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const MEHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';

    /**
     * @var string[][] E.g.: ['/' => ['GET' => ShowIndex::class]]
     */
    private $routes;

    private const METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS'
    ];

    /**
     * @param string $method HTTP method e.g. GET. See self::METHOD_* constants.
     * @param string $path Request path e.g. /admin
     * @param string $controller The fully qualified class name
     * 
     * @return void
     * 
     * @throws RouterException If path is already configured, or unsupported method
     */
    public function configurePath(string $method, string $path, string $controller): void
    {
        if (!in_array($method, self::METHODS)) {
            throw new RouterException("Method not supported: {$method}");
        }

        if (isset($this->routes[$method][$path])) {
            throw new RouterException("Path \"{$path}\" ({$method}) is already configured.");
        }

        if (preg_match("~{$path}~", '') === false) {
            throw new RouterException("Invalid path: {$path}");
        }

        $this->routes[$method][$path] = $controller;
    }

    public function processRequest(ServerRequestInterface $request): ?RouterResult
    {
        return $this->resolvePath($request);
    }

    private function resolvePath(ServerRequestInterface $request): ?RouterResult
    {
        $result = null;
        $method = strtoupper($request->getMethod());
        $uri = $request->getUri();
        $requested_path = rtrim($uri->getPath(), '/');
        $matches = [];
        $registered_paths = $this->routes[$method] ?? [];

        if (isset($registered_paths[$requested_path])) {
            // Static routes
            $controller_name = $registered_paths[$requested_path];
            $path_result = new PathResult($matches);
            $query_result = new QueryResult($uri->getQuery());
            $result = new RouterResult($controller_name, $path_result, $query_result);
        } else {
            // Dynamic routes
            foreach ($registered_paths as $path_pattern => $controller_name) {
                if (preg_match("~^{$path_pattern}$~u", $requested_path, $matches) === 1) {
                    $path_result = new PathResult($matches);
                    $query_result = new QueryResult($uri->getQuery());
                    $result = new RouterResult($controller_name, $path_result, $query_result);
                    break;
                }
            }
        }

        return $result;
    }
}
