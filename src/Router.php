<?php
namespace NaiveRouter;

use NaiveRouter\Exception\RouterException;
use Psr\Http\Message\ServerRequestInterface;

class Router
{

    /**
     * @var string[][] E.g.: ['/' => ['GET' => ShowIndex::class]]
     */
    private $config;

    public const METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    /**
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

    public function run(ServerRequestInterface $request): ?RouterResult
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
                    $query_result = new QueryResult($uri->getQuery());
                    $result = new RouterResult($method_controller[$method], $matches, $query_result);
                    break;
                }
            }
        }

        return $result;
    }
}
