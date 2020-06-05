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
     * Used by implementors
     */
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

    /**
     * E.g.
     * [
     *  'GET' => [
     *      'user' => [
     *          'terms' => 'Controller 1',
     *          '(?<uuid>[0-9a-f\-]{36})' => 'Controller 2',
     *      ]
     *  ]
     * ]
     */
    private $configured_paths = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => [],
        'OPTIONS' => [],
        'HEAD' => [],
    ];

    /**
     * Used by validators
     */
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

        if (preg_match("~{$path}~", '') === false) {
            throw new RouterException("Invalid path: {$path}");
        }

        ltrim($path, '/');
        $segments = explode('/', $path);
        $current  = &$this->configured_paths[$method];

        foreach ($segments as $segment) {
            if (isset($current[$segment])) {
                $current = &$current[$segment];
                continue;
            } else {
                $current[$segment] = [];
                // Sort dynamic segmants to be first
                ksort($current);

                $current = &$current[$segment];
            }
        }

        $current = $controller;
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

        ltrim($requested_path, '/');
        $requested_segments  = explode('/', $requested_path);
        $configured_segments = $this->configured_paths[$method];

        $controller_name = $this->traverse($requested_segments, $configured_segments);

        $path_result     = new PathResult([]);
        $query_result    = new QueryResult($uri->getQuery());
        $result          = new RouterResult($controller_name, $path_result, $query_result);


        return $result;
    }

    private function traverse(array $requested_segments, array $configured_segments): ?string
    {
        static $index = 0;

        $requested_segment = $requested_segments[$index++];

        if (isset($configured_segments[$requested_segment])) {
            // Static route exists
            $current = &$configured_segments[$requested_segment];
        } else {
            // Check for dynamic routes
            $matches = [];
            $keys    = array_keys($configured_segments);

            foreach ($keys as $key) {
                // Note: URLs are case sensitive https://www.w3.org/TR/WD-html40-970708/htmlweb.html
                if (preg_match("~^{$key}$~", $requested_segment, $matches) === 1) {
                    $current = &$configured_segments[$key];
                    break;
                }
            }
            if (empty($matches)) {
                // Could not find any dynamic routes
                return null;
            }
        }

        if (is_string($current)) {
            // Current is a handler and not a path segment (array)
            return $current;
        }

        return $this->traverse($requested_segments, $current);
    }
}