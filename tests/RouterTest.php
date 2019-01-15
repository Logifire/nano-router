<?php
namespace NaiveRouter\Tests;

use NaiveContainer\ContainerFactory;
use NaiveRouter\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    private function routerSetup(): Router
    {
        $controller_class = 'MockController';

        $controller = new MockController();

        $container_factory = new ContainerFactory();

        $container_factory->set($controller_class, $controller);

        $container = $container_factory->createContainer();

        $router = new Router($container);
        $router->configurePath('GET', '/profiles/(?<uuid>[0-9a-f\-]{36})', $controller_class);
        $router->configurePath('GET', '/profiles/(?<id>\d+)', $controller_class);
        $router->configurePath('GET', '/profiles', 'Invalid');
        $router->configurePath('GET', '/profiles/abc', 'Invalid');
        $router->configurePath('GET', '/profiles/(?<id>\d+)/children', 'Invalid');

        return $router;
    }

    public function testRouter()
    {
        $router = $this->routerSetup();

        $integer_id_path = '/profiles/1234';
        $server_request = new ServerRequest('GET', "{$integer_id_path}/#test");

        $response = $router->run($server_request);

        $stream = $response->getBody();
        $stream->rewind();
        $body = $stream->getContents();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains(MockController::RESPONSE_BODY, $body);
        $this->assertContains($integer_id_path, $body, 'The correct controller is called');
    }

    public function testSimilarPath()
    {
        $router = $this->routerSetup();

        $uuid_path = '/profiles/a7598692-307e-4782-8229-8429d32ba42f';
        $server_request = new ServerRequest('GET', "{$uuid_path}/#test");

        $response = $router->run($server_request);

        $stream = $response->getBody();
        $stream->rewind();
        $body = $stream->getContents();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains($uuid_path, $body, 'The correct controller is called');
    }
}
