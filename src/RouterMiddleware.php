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
    private $factory;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router, ContainerFactory $factory)
    {

        $this->router = $router;
        $this->factory = $factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->router->run($request);

        $this->factory->set(ServerRequestInterface::class, $request);
        $this->factory->set(RouterResult::class, $result);
        $container = $this->factory->createContainer();

        if ($result && $container->has($result->getController())) {
            /* @var $controller Controller */
            $controller = $container->get($result->getController());
            return $controller->run();
        }

        return $handler->handle($request);
    }
}
