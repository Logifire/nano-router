<?php

use NanoRouter\Router;
use NanoRouter\RouterCore;

require dirname(__DIR__).'/vendor/autoload.php';

class SpeedBenchmark
{

    private function createNanoRouter($routeIndex)
    {
        $router = new RouterCore();
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

    private function testPsr7(RouterCore $router, $url)
    {

        $time = 0;
        for ($i = 0; $i < 10000; $i++) {
            $t1   = microtime(true);
            $router->lookup('GET', $url);
            $t2   = microtime(true);
            $time += ($t2 - $t1);
        }
        return $time;
    }

    private function test(\TreeRoute\Router $router, $url)
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

        $routePatterns = [
            '/%section%/(?<param1>[a-z]+)' => '/%section%/1',
            '/%section%/(?<param1>[a-z]+)/(?<param2>[a-z]+)' => '/%section%/1/2',
            '/%section%/(?<param1>[a-z]+)/(?<param2>[a-z]+)/full' => '/%section%/1/2/full',
            '/%section%/%subsection%/(?<param1>[0-9]+)' => '/%section%/%subsection%/1',
            '/%section%/%subsection%/(?<param1>[0-9]+)/(?<param2>[a-z]+)' => '/%section%/%subsection%/1/hello',
            '/%section%/%subsection%/(?<param1>[0-9]+)/(?<param2>[a-z]+)/full' => '/%section%/%subsection%/1/hello/full'
        ];

        $routeIndexRegex = [];
        foreach ($sections as $section) {
            foreach ($subsections as $subsection) {
                foreach ($routePatterns as $routePattern => $urlPattern) {
                    $route                   = str_replace(['%section%', '%subsection%'], [$section, $subsection],
                        $routePattern);
                    $url                     = str_replace(['%section%', '%subsection%'], [$section, $subsection],
                        $urlPattern);
                    $routeIndexRegex[$route] = $url;
                }
            }
        }

        $urls = array_values($routeIndex);

        $t1        = microtime(true);
        $fastRoute = $this->createNanoRouter($routeIndexRegex);
        $t2        = microtime(true);

        $t3        = microtime(true);
        $treeRoute = $this->createTreeRoute($routeIndex);
        $t4        = microtime(true);

        echo 'NanoRouter init time: '.($t2 - $t1).PHP_EOL;

        echo 'TreeRoute init time: '.($t4 - $t2).PHP_EOL;

        $fastRouteResultFirst = $this->testPsr7($fastRoute, $urls[0]);
        $treeRouteResultFirst = $this->test($treeRoute, $urls[0]);

        $fastRouteResultMiddle = $this->testPsr7($fastRoute, $urls[round(sizeof($urls) / 2)]);
        $treeRouteResultMiddle = $this->test($treeRoute, $urls[round(sizeof($urls) / 2)]);

        $fastRouteResultLast = $this->testPsr7($fastRoute, $urls[sizeof($urls) - 1]);
        $treeRouteResultLast = $this->test($treeRoute, $urls[sizeof($urls) - 1]);


        $fastRouteResultNotFound = $this->testPsr7($fastRoute, '/not/found/url');
        $treeRouteResultNotFound = $this->test($treeRoute, '/not/found/url');

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
(new SpeedBenchmark())->testSpeed();
