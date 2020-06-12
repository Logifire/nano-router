<?php

namespace NanoRouter\Tests;

use NanoRouter\Router;
use NanoRouter\RouterCore;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    private function configureRouter(string $controller_name): Router
    {
        $router = new Router(new RouterCore());
        $router->configurePath(Router::METHOD_GET, '/profiles/(?<uuid>[0-9a-f\-]{36})', $controller_name);
        $router->configurePath(Router::METHOD_GET, '/profiles/(?<id>\d+)', $controller_name);
        $router->configurePath(Router::METHOD_GET, '/profiles', 'Invalid');
        $router->configurePath(Router::METHOD_GET, '/profiles/abc', 'Invalid');
        $router->configurePath(Router::METHOD_GET, '/profiles/(?<id>\d+)/children', 'Invalid');
        $router->configurePath(Router::METHOD_GET, '/profiles/static', $controller_name);
        $router->configurePath(Router::METHOD_GET, '/(?<id>\d+)', 'Invalid');
        $router->configurePath(Router::METHOD_GET, '/category/(?<id>\d+)', 'Invalid');
        $router->configurePath(Router::METHOD_GET, '/', $controller_name);

        return $router;
    }

    public function testRouter()
    {
        $controller_name = MockController::class;
        $router          = $this->configureRouter($controller_name);

        $id              = 1234;
        $integer_id_path = "/profiles/{$id}";
        $server_request  = new ServerRequest('GET', "{$integer_id_path}/?foo=bar&baz=xyz#test");

        $router_result = $router->processRequest($server_request);
        $this->assertSame($controller_name, $router_result->getControllerName());
        $path_result   = $router_result->getPathResult();
        $this->assertTrue($path_result->hasInteger('id'));
        $this->assertSame($id, $path_result->getInetger('id'));
    }

    public function testFrontpage()
    {
        $controller_name = MockController::class;
        $router          = $this->configureRouter($controller_name);

        $server_request = new ServerRequest('GET', '');

        $router_result = $router->processRequest($server_request);
        $this->assertSame($controller_name, $router_result->getControllerName(), 'Frontpage can be handled');
    }

    public function testSimilarPath()
    {
        $controller_name = MockController::class;
        $router          = $this->configureRouter($controller_name);

        $uuid           = 'a7598692-307e-4782-8229-8429d32ba42f';
        $uuid_path      = "/profiles/{$uuid}";
        $server_request = new ServerRequest('GET', "{$uuid_path}/#test");

        $router_result = $router->processRequest($server_request);
        $path_result   = $router_result->getPathResult();
        $this->assertSame($controller_name, $router_result->getControllerName());
        $this->assertTrue($path_result->hasString('uuid'));
        $this->assertSame($uuid, $path_result->getString('uuid'));
    }

    public function testQueryResult()
    {
        $controller_name = MockController::class;
        $router          = $this->configureRouter($controller_name);

        $server_request = new ServerRequest('GET', "/profiles/1234/?first=value&arr[]=foo+bar&arr[]=baz");

        $router_result = $router->processRequest($server_request);
        $query_result  = $router_result->getQueryResult();

        $this->assertTrue($query_result->hasString('first'));
        $this->assertFalse($query_result->hasString('First'), 'Parameters are case sensitive');
        $this->assertSame('value', $query_result->getString('first'));

        $this->assertFalse($query_result->hasString('arr'));

        $this->assertTrue($query_result->hasCollection('arr'));

        $collection = $query_result->getCollection('arr');
        $this->assertCount(2, $collection, 'Two rows in query collection');
    }

    public function testStaticRoute()
    {
        $controller_name = MockController::class;
        $router          = $this->configureRouter($controller_name);

        $server_request = new ServerRequest('GET', "/profiles/static/");
        $router_result  = $router->processRequest($server_request);
        $this->assertSame($controller_name, $router_result->getControllerName());
    }

    public function testInvalidPath()
    {
        $controller_name = MockController::class;
        $router          = $this->configureRouter($controller_name);

        // Paths are case sensitive
        $server_request = new ServerRequest('GET', "/Profiles/Static/");
        $router_result  = $router->processRequest($server_request);
        $this->assertNull($router_result);
    }
}