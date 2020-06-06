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
     * E.g.
     * [
     *  'GET' => [
     *      'controller' => 'Controller 0',
     *      'statics' => [
     *            'user' => [
     *                'controller' => 'Controller 1',
     *                'statics' => [
     *                    'sign-up' => [
     *                        'controller' => 'Controller 2',
     *                        'statics' => [],
     *                        'dynamics' => [],
     *                    ],
     *                ],
     *                'dynamics' => [
     *                                '(?<uuid>[0-9a-f\-]{36})' => [
     *                                       'controller' => 'Controller 3',
     *                                       'statics' => [],
     *                                       'dynamics' => [],
     *                                    ]
     *                              ],
     *            ]
     *        ]
     *      ],
     *      'dynamics' => [],
     *  ]
     * ]
     */
    private $configured_paths = [
        'GET' => ['controller' => '', 'statics' => [], 'dynamics' => []],
        'POST' => ['controller' => '', 'statics' => [], 'dynamics' => []],
        'PUT' => ['controller' => '', 'statics' => [], 'dynamics' => []],
        'DELETE' => ['controller' => '', 'statics' => [], 'dynamics' => []],
        'PATCH' => ['controller' => '', 'statics' => [], 'dynamics' => []],
        'OPTIONS' => ['controller' => '', 'statics' => [], 'dynamics' => []],
        'HEAD' => ['controller' => '', 'statics' => [], 'dynamics' => []],
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

        $path             = trim($path, '/');
        $segments         = explode('/', $path);
        $current          = &$this->configured_paths[$method];
        end($segments);
        $total_iterations = key($segments);

        foreach ($segments as $iteration => $segment) {
            $is_dynamic = preg_match('/\[.+?\]/', $segment, $matches) === 1 ? true : false;
            $type       = $is_dynamic ? 'dynamics' : 'statics';

            if (isset($current[$type][$segment])) {
                // Existing node
                $current = &$current[$type][$segment];
                continue;
            } else {
                // Create new leaf
                $current[$type][$segment] = ['controller' => '', 'statics' => [], 'dynamics' => []];

                $current = &$current[$type][$segment];

                if ($total_iterations === $iteration) {
                    break;
                }
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
                              int $iteration = 0, array $matched_dynamics = []): array
    {
        $requested_segment = $requested_segments[$iteration++];

        if (isset($configured_segments['statics'][$requested_segment])) {
            // Static route exists
            $current = &$configured_segments['statics'][$requested_segment];
        } else {
            // Check for dynamic routes
            $regex_patterns = array_keys($configured_segments['dynamics']);

            foreach ($regex_patterns as $regex_pattern) {
                // Note: URLs are case sensitive https://www.w3.org/TR/WD-html40-970708/htmlweb.html
                if (preg_match("~^{$regex_pattern}$~", $requested_segment, $matches) === 1) {
                    next($matches);
                    $group_name                    = key($matches) ?: $regex_pattern; // Is it a named group
                    $matched_dynamics[$group_name] = $requested_segment;
                    $current                       = &$configured_segments['dynamics'][$regex_pattern];
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
                'path_keys' => $matched_dynamics,
            ];
            return $result;
        }

        return $this->traverse($requested_segments, $current, $total_iterations, $iteration, $matched_dynamics);
    }
}