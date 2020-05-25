<?php

namespace NanoRouter;

use NanoRouter\Exception\RouterException;
use NanoRouter\Result\PathResult;
use NanoRouter\Result\QueryResult;
use NanoRouter\Result\RouterResult;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    public const METHOD_GET     = 'GET';
    public const METHOD_POST    = 'POST';
    public const MEHOD_PUT      = 'PUT';
    public const METHOD_DELETE  = 'DELETE';
    public const METHOD_PATCH   = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_HEAD    = 'HEAD';

    /**
     * @var string[][] E.g.: ['/' => ['GET' => ShowIndex::class]]
     */
    private $static_routes;
    private $dynamic_routes;

    private const METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS',
        'HEAD'
    ];

    /**
     * @param string $method HTTP method e.g. GET. See self::METHOD_* constants.
     * @param string $path Request path e.g. /profiles/(?<uuid>[0-9a-f\-]{36})
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

        if (isset($this->static_routes[$method][$path])) {
            throw new RouterException("Path \"{$path}\" ({$method}) is already configured.");
        }

        if (preg_match("~{$path}~", '') === false) {
            throw new RouterException("Invalid path: {$path}");
        }

        $is_dynamic_path = preg_match('~/\([^/]+\)~', $path) === 1;

        if ($is_dynamic_path) {
            $this->dynamic_routes[$method][$path] = $controller;
        } else {
            $this->static_routes[$method][$path] = $controller;
        }
    }

    public function processRequest(ServerRequestInterface $request): ?RouterResult
    {
        return $this->resolvePath($request);
    }

    private function resolvePath(ServerRequestInterface $request): ?RouterResult
    {
        $result         = null;
        $method         = strtoupper($request->getMethod());
        $uri            = $request->getUri();
        $requested_path = rtrim($uri->getPath(), '/');
        $matches        = [];

        if (isset($this->static_routes[$method][$requested_path])) {
            // Static routes
            $controller_name = $this->static_routes[$method][$requested_path];
            $path_result     = new PathResult($matches);
            $query_result    = new QueryResult($uri->getQuery());
            $result          = new RouterResult($controller_name, $path_result, $query_result);
        } else {
            // Dynamic routes
            $registered_paths = $this->dynamic_routes[$method] ?? [];
            foreach ($registered_paths as $path_pattern => $controller_name) {
                if (preg_match("~^{$path_pattern}$~u", $requested_path, $matches) === 1) {
                    $path_result  = new PathResult($matches);
                    $query_result = new QueryResult($uri->getQuery());
                    $result       = new RouterResult($controller_name, $path_result, $query_result);
                    break;
                }
            }
        }

        return $result;
    }
}
