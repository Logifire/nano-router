<?php

namespace NanoRouter;

use NanoRouter\Exception\RouterException;

class RouterKernel
{

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
     * @param string $controller_name The fully qualified class name
     *
     * @return void
     *
     * @throws RouterException If path is already configured, or unsupported method
     */
    public function configurePath(string $method, string $path,
        string $controller_name): void
    {
        if (!in_array($method, self::METHODS)) {
            throw new RouterException("Method not supported: {$method}");
        }

        if (preg_match("~{$path}~", '') === false) {
            throw new RouterException("Invalid path: {$path}");
        }

        $path = trim($path, '/');
        $segments = explode('/', $path);
        $current = &$this->configured_paths[$method];
        $total_iterations = count($segments) - 1;

        foreach ($segments as $iteration => $segment) {
            $is_dynamic = preg_match('~[\[\]\<\>\\\\]~', $segment) === 1 ? true : false;
            $type = $is_dynamic ? 'dynamics' : 'statics';

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

        $current['controller'] = $controller_name;
    }

    /**
     *
     * @param string $method e.g. GET
     * @param string $requested_path e.g. /user/1234
     * @return array [controller_name => string, group_name => []]
     */
    public function lookup(string $method, string $requested_path): array
    {
        $method = strtoupper($method);
        $requested_path = trim($requested_path, '/');
        $requested_segments = explode('/', $requested_path);
        $configured_segments = $this->configured_paths[$method];

        return $this->traverse($requested_segments, $configured_segments,
                count($requested_segments));
    }

    private function traverse(array $requested_segments,
        array $configured_segments, int $total_iterations, int $iteration = 0,
        array $matched_dynamics = []): array
    {
        $current = null;
        $requested_segment = $requested_segments[$iteration++];

        if (isset($configured_segments['statics'][$requested_segment])) {
            // Static route exists
            $current = &$configured_segments['statics'][$requested_segment];
        } else {
            // Check for dynamic routes
            foreach ($configured_segments['dynamics'] as $regex_pattern => $segment) {
                // Note: URLs are case sensitive https://www.w3.org/TR/WD-html40-970708/htmlweb.html
                if (preg_match("~^{$regex_pattern}$~", $requested_segment,
                        $matches) === 1) {
                    $matched_keys = array_keys($matches);
                    $group_name = $matched_keys[1] ?? $regex_pattern; // Is it a named group in regex
                    $matched_dynamics[$group_name] = $requested_segment;
                    $current = &$segment;
                    break;
                }
            }
        }

        if ($iteration === $total_iterations || $current === null) {
            // Last iteration or no matches
            $result = [
                'controller_name' => $current['controller'] ?? '',
                'group_name' => $matched_dynamics,
            ];
            return $result;
        }

        return $this->traverse($requested_segments, $current, $total_iterations,
                $iteration, $matched_dynamics);
    }
}
