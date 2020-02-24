<?php
namespace NanoRouter;

use NanoRouter\Exception\RouterException;
use Psr\Http\Message\ServerRequestInterface;

class Router
{

    /**
     * @var string[][] E.g.: ['/' => ['GET' => ShowIndex::class]]
     */
    private $config;

    private const METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    /**
     * @param string $method HTTP method e.g. GET
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

        if (isset($this->config[$path][$method])) {
            throw new RouterException("Path \"{$path}\" ({$method}) is already configured.");
        }

        if (@preg_match("~{$path}~", '') === false) {
            throw new RouterException("Invalid path: {$path}");
        }

        $this->config[$path] = [$method => $controller];
    }

    public function processRequest(ServerRequestInterface $request): ?RouterResult
    {
        return $this->resolvePath($request);
    }

    private function resolvePath(ServerRequestInterface $request): ?RouterResult
    {
        $result = null;
        $method = $request->getMethod();
        $uri = $request->getUri();
        $path = rtrim($uri->getPath(), '/');
        $matches = [];

        foreach ($this->config as $pattern => $method_controller) {
            if (@preg_match("~^{$pattern}$~iu", $path, $matches) === 1) {
                if (isset($method_controller[$method])) {
                    $controller_name = $method_controller[$method];
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
