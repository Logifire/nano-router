<?php
namespace NaiveRouter\Tests;

use NaiveContainer\Container;
use NaiveContainer\ContainerFactory;
use NaiveMiddleware\RequestHandler;
use NaiveRouter\Router;
use NaiveRouter\RouterMiddleware;
use NaiveRouter\RouterResult;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class MiddlewareTest extends TestCase
{

    public function testResolving()
    {
        $container_factory = new ContainerFactory();
        $container_factory->register(MockController::class, function(Container $c) {
            $server_request = $c->get(ServerRequestInterface::class);
            $result = $c->get(RouterResult::class);
            return new MockController($server_request, $result);
        });

        $user_id = 123456;
        $server_request = new ServerRequest('GET', "/user/{$user_id}?foo=bar");

        $router = new Router();
        $router->configurePath('GET', '/user/(?<user_id>\d+)', MockController::class);

        $response_factory = new Psr17Factory();
        $handler = new RequestHandler($response_factory);
        $handler->addMiddleware(new RouterMiddleware($router, $container_factory));


        $response = $handler->handle($server_request);

        $body = (string) $response->getBody();
        $this->assertContains(MockController::RESPONSE_BODY, $body);
        $this->assertContains("User ID: {$user_id}", $body);
    }
}
