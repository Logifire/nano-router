<?php
namespace NaiveRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterMiddleware implements MiddlewareInterface
{

    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {

        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->router->run($request);

        if (!$response) {
            return $handler->handle($request);
        }

        return $response;
    }
}
