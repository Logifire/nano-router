<?php
namespace NaiveRouter\Tests;

use NaiveContainer\ContainerFactory;
use NaiveRouter\Controller;
use NaiveRouter\Router;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterTest extends TestCase
{

    public function testRouter()
    {
        $name = 'MockController';
        $response_msg = 'MockControllerResponse';

        $controller = new Class implements Controller {

            public function run(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'MockControllerResponse');
            }
        };

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
        $this->assertSame($response_msg, $stream->getContents());
    }
}
