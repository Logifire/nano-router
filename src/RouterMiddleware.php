<?php

namespace NanoRouter;

use NanoRouter\Result\RouterResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterMiddleware implements MiddlewareInterface
{

    private Router $router;

    public function __construct(Router $router)
    {

        $this->router = $router;
    }

    public function process(ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        $router_result = $this->router->processRequest($request);
        $request = $request->withAttribute(RouterResult::class, $router_result);
        $response = $handler->handle($request);
        return $response;
    }
}
