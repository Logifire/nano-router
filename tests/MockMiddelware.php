<?php
namespace NanoRouter\Tests;

use NanoContainer\ContainerFactory;
use NanoRouter\Controller;
use NanoRouter\PathResult;
use NanoRouter\QueryResult;
use NanoRouter\RouterResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MockMiddelware implements MiddlewareInterface
{

    /**
     * @var ContainerFactory
     */
    private $container_factory;

    public function __construct(ContainerFactory $container_factory)
    {

        $this->container_factory = $container_factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouterResult $router_result */
        $router_result = $request->getAttribute(RouterResult::class);
        $controller_name = $router_result->getControllerName();

        $this->container_factory->set(PathResult::class, $router_result->getPathResult());
        $this->container_factory->set(QueryResult::class, $router_result->getQueryResult());
        $this->container_factory->set(ServerRequestInterface::class, $request);

        $container = $this->container_factory->createContainer();

        /** @var Controller $controller */
        $controller = $container->get($controller_name);

        return $controller->buildResponse();
    }
}
