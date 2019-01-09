<?php
namespace NaiveRouter\Tests;

use NaiveContainer\ContainerFactory;
use NaiveRouter\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    public function testRouter()
    {
        $name = 'MockController';

        $controller = new MockController();

        $container_factory = new ContainerFactory();

        $container_factory->set($name, $controller);

        $container = $container_factory->createContainer();

        $router = new Router($container);
        $router->configurePath('get', '/', $name);

        $server_request = new ServerRequest('get', '/');

        $response = $router->run($server_request);
        $this->assertSame(200, $response->getStatusCode());
        $stream = $response->getBody();
        $stream->rewind();
        $this->assertSame(MockController::RESPONSE_BODY, $stream->getContents());
    }
}
