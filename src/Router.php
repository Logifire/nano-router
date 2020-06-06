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
     * Used for input validation
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

        $path       = ltrim($path, '/');
        $segments   = explode('/', $path);
        $current    = &$this->configured_paths[$method];
        end($segments);
        $last_index = key($segments);

        foreach ($segments as $index => $segment) {
            if (isset($current[$segment])) {
                $current = &$current[$segment]['children'];
                continue;
            } else {
                $current[$segment] = ['controller' => '', 'children' => []];
                // Sort dynamic segmants to be first
                ksort($current);

                if ($last_index === $index) {
                    $current = &$current[$segment];
                    break;
                }
                $current = &$current[$segment]['children'];
            }
        }

        $current['controller'] = $controller;
    }

    /**
     * @param ServerRequestInterface $request
     * @return RouterResult|null
     */
    public function processRequest(ServerRequestInterface $request): ?RouterResult
    {
        $method         = $request->getMethod();
        $uri            = $request->getUri();
        $requested_path = $uri->getPath();

        $result = $this->lookUp($method, $requested_path);

        return $result;
    }

    /**
     *
     * @param string $method e.g. GET
     * @param string $requested_path e.g. /user/1234
     * @return RouterResult|null
     */
    public function lookUp(string $method, string $requested_path): array
    {
        $result              = null;
//        $method              = strtoupper($method);
        $requested_path      = trim($requested_path, '/');
        $requested_segments  = explode('/', $requested_path);
        $configured_segments = $this->configured_paths[$method];

        return $this->traverse($requested_segments, $configured_segments, count($requested_segments));
        ["controller_name" => $controller_name, "path_keys" => $path_keys] = $this->traverse($requested_segments,
            $configured_segments, count($requested_segments));

        $path_result = new PathResult($path_keys);
        $result      = new RouterResult($controller_name, $path_result);

        return $result;
    }

    private function traverse(array $requested_segments, array $configured_segments, int $total_iterations,
                              int $iteration = 0, array $dynamic_segment_keys = []): array
    {
        $requested_segment = $requested_segments[$iteration++];

        if (isset($configured_segments[$requested_segment])) {
            // Static route exists
            if ($iteration === $total_iterations) {
                // Last segment
                $current = &$configured_segments[$requested_segment];
            } else {
                $current = &$configured_segments[$requested_segment]['children'];
            }
        } else {
            // Check for dynamic routes
            $regex_keys = array_keys($configured_segments); // Represent the children array from last iteration

            foreach ($regex_keys as $regex_key) {
                // Note: URLs are case sensitive https://www.w3.org/TR/WD-html40-970708/htmlweb.html
                if (preg_match("~^{$regex_key}$~", $requested_segment, $matches) === 1) {
                    next($matches);
                    $named_key                        = key($matches) ?: $regex_key;
                    $dynamic_segment_keys[$named_key] = $requested_segment;
                    if ($iteration === $total_iterations) {
                        // Last segment
                        $current = &$configured_segments[$regex_key];
                    } else {
                        $current = &$configured_segments[$regex_key]['children'];
                    }
                    break;
                }
            }

            if (empty($matches)) {
                // Could not find any dynamic routes
                $result = [
                    'controller_name' => '',
                    'path_keys' => [],
                ];
                return $result;
            }
        }

        if ($iteration === $total_iterations) {
            // Current is a handler and not a path segment (array)
            $result = [
                'controller_name' => $current['controller'],
                'path_keys' => $dynamic_segment_keys,
            ];
            return $result;
        }

        return $this->traverse($requested_segments, $current, $total_iterations, $iteration, $dynamic_segment_keys);
    }
}