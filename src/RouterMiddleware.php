<?php
namespace NanoRouter;

use NanoContainer\Factory as ContainerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * NOTE: This middleware implementation requires the logifire/naive-container package
 */
class RouterMiddleware implements MiddlewareInterface
{

    /**
     * @var ContainerFactory
     */
    private $container_factory;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router, ContainerFactory $container_factory)
    {

        $this->router = $router;
        $this->container_factory = $container_factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router_result = $this->router->processRequest($request);
        $path_result = $router_result->getPathResult();
        $query_result = $router_result->getQueryResult();
        $controller_name = $router_result->getControllerName();

        $this->container_factory->set(ServerRequestInterface::class, $request);

        $this->container_factory->set(PathResult::class, $path_result);
        $this->container_factory->set(QueryResult::class, $query_result);

        $container = $this->container_factory->createContainer();

        if (!$router_result || !$container->has($controller_name)) {
            // Can't handle the request at this point, delegate to next middleware
            return $handler->handle($request);
        }

        /** @var Controller $controller */
        $controller = $container->get($controller_name);
        $response = $controller->buildResponse();
        return $response;
    }
}
