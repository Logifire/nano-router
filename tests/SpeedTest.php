<?php

use NanoRouter\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

require dirname(__DIR__).'/vendor/autoload.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SpeedTest
{

    private function createNanoRouter($routeIndex)
    {
        $router = new Router();
        $i      = 0;
        foreach ($routeIndex as $route => $url) {
            $router->configurePath(Router::METHOD_GET, $route, 'handler'.$i);
            $i++;
        }
        return $router;
    }

    private function createTreeRoute($routeIndex)
    {
        $router = new \TreeRoute\Router();
        $i      = 0;
        foreach ($routeIndex as $route => $url) {
            $router->addRoute('GET', $route, 'handler'.$i);
            $i++;
        }
        return $router;
    }

    private function testPsr7(Router $router, $routeIndex, $url)
    {

        $time = 0;
        for ($i = 0; $i < 10000; $i++) {
            $t1             = microtime(true);
            $server_request = new ServerRequest('GET', $url);
            $router->processRequest($server_request);
            $t2             = microtime(true);
            $time           += ($t2 - $t1);
        }
        return $time;
    }

    private function test(\TreeRoute\Router $router, $routeIndex, $url)
    {
        $time = 0;
        for ($i = 0; $i < 10000; $i++) {
            $t1   = microtime(true);
            $router->dispatch('GET', $url);
            $t2   = microtime(true);
            $time += ($t2 - $t1);
        }
        return $time;
    }

    public function testSpeed()
    {
        $sections    = ['news', 'projects', 'users', 'tasks', 'articles', 'documents', 'photos'];
        $subsections = ['all', 'new', 'popular', 'discussed', 'hot', 'my'];

        $routePatterns = [
            '/%section%/{param1}' => '/%section%/1',
            '/%section%/{param1}/{param2}' => '/%section%/1/2',
            '/%section%/{param1}/{param2}/full' => '/%section%/1/2/full',
            '/%section%/%subsection%/{param1:[0-9]+}' => '/%section%/%subsection%/1',
            '/%section%/%subsection%/{param1:[0-9]+}/{param2:[a-z]+}' => '/%section%/%subsection%/1/hello',
            '/%section%/%subsection%/{param1:[0-9]+}/{param2:[a-z]+}/full' => '/%section%/%subsection%/1/hello/full'
        ];

        $routeIndex = [];
        foreach ($sections as $section) {
            foreach ($subsections as $subsection) {
                foreach ($routePatterns as $routePattern => $urlPattern) {
                    $route              = str_replace(['%section%', '%subsection%'], [$section, $subsection],
                        $routePattern);
                    $url                = str_replace(['%section%', '%subsection%'], [$section, $subsection],
                        $urlPattern);
                    $routeIndex[$route] = $url;
                }
            }
        }

//        var_dump($routeIndex);

        $urls = array_values($routeIndex);

        $t1        = microtime(true);
        $fastRoute = $this->createNanoRouter($routeIndex);
        $t2        = microtime(true);

        $t3        = microtime(true);
        $treeRoute = $this->createTreeRoute($routeIndex);
        $t4        = microtime(true);

        echo 'NanoRouter init time: '.($t2 - $t1).PHP_EOL;

        echo 'TreeRoute init time: '.($t4 - $t2).PHP_EOL;

        $fastRouteResultFirst = $this->testPsr7($fastRoute, $routeIndex, $urls[0]);
        $treeRouteResultFirst = $this->test($treeRoute, $routeIndex, $urls[0]);

        $fastRouteResultMiddle = $this->testPsr7($fastRoute, $routeIndex, $urls[round(sizeof($urls) / 2)]);
        $treeRouteResultMiddle = $this->test($treeRoute, $routeIndex, $urls[round(sizeof($urls) / 2)]);

        $fastRouteResultLast = $this->testPsr7($fastRoute, $routeIndex, $urls[sizeof($urls) - 1]);
        $treeRouteResultLast = $this->test($treeRoute, $routeIndex, $urls[sizeof($urls) - 1]);


        $fastRouteResultNotFound = $this->testPsr7($fastRoute, $routeIndex, '/not/found/url');
        $treeRouteResultNotFound = $this->test($treeRoute, $routeIndex, '/not/found/url');

        echo 'NanoRouter first route time: '.$fastRouteResultFirst.PHP_EOL;
        echo 'TreeRoute first route time: '.$treeRouteResultFirst.PHP_EOL;

        echo 'NanoRouter middle route time: '.$fastRouteResultMiddle.PHP_EOL;
        echo 'TreeRoute middle route time: '.$treeRouteResultMiddle.PHP_EOL;

        echo 'NanoRouter last route time: '.$fastRouteResultLast.PHP_EOL;
        echo 'TreeRoute last route time: '.$treeRouteResultLast.PHP_EOL;

        echo 'NanoRouter not found time: '.$fastRouteResultNotFound.PHP_EOL;
        echo 'TreeRoute not found time: '.$treeRouteResultNotFound.PHP_EOL;
    }
}
//(new SpeedTest())->testSpeed();

$segments = [
    '[a-z]+' => [
        '[0-9]+' => 'Controller'
    ],
    'user' => [
        '[a-z]+' => 'Controller 2',
        'terms' => 'Controller 3',
        '[0-9]+' => 'Controller 4',
    ]
];

$path = 'user/hello';

function resolve(array $requested_segments, array $configured_segments): ?string
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
        // TODO: Order the keys on configuration
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

    return resolve($requested_segments, $current);
}
var_dump(resolve(explode('/', $path), $segments));

echo 'Blag';
