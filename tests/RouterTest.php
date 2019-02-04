<?php
namespace NaiveRouter\Tests;

use NaiveRouter\Router;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    private function configureRouter(string $controller_class): Router
    {
        $router = new Router();
        $router->configurePath('GET', '/profiles/(?<uuid>[0-9a-f\-]{36})', $controller_class);
        $router->configurePath('GET', '/profiles/(?<id>\d+)', $controller_class);
        $router->configurePath('GET', '/profiles', 'Invalid');
        $router->configurePath('GET', '/profiles/abc', 'Invalid');
        $router->configurePath('GET', '/profiles/(?<id>\d+)/children', 'Invalid');

        return $router;
    }

    public function testRouter()
    {
        $controller_class = 'MockController';
        $router = $this->configureRouter($controller_class);

        $id = 1234;
        $integer_id_path = "/profiles/{$id}";
        $server_request = new ServerRequest('GET', "{$integer_id_path}/#test");

        $result = $router->run($server_request);
        $this->assertSame($controller_class, $result->getController());
        $this->assertTrue($result->hasInteger('id'));
        $this->assertSame($id, $result->getInetger('id'));
    }

    public function testSimilarPath()
    {
        $controller_class = 'MockController';
        $router = $this->configureRouter($controller_class);

        $uuid = 'a7598692-307e-4782-8229-8429d32ba42f';
        $uuid_path = "/profiles/{$uuid}";
        $server_request = new ServerRequest('GET', "{$uuid_path}/#test");

        $result = $router->run($server_request);
        $this->assertSame($controller_class, $result->getController());
        $this->assertTrue($result->hasString('uuid'));
        $this->assertSame($uuid, $result->getString('uuid'));
    }
}
