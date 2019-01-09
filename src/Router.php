<?php
namespace NaiveRouter;

use NaiveFramework\Controller;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UI\Exception\RuntimeException;

class Router
{

    /**
     * @var string[][] E.g.: ['/' => ['GET' => ShowIndex::class]]
     */
    private $config;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;
    }

    /**
     * Please add a "Not Found" controller, method "get", path 404
     * 
     * @throws RuntimeException If path is already configured
     */
    public function configurePath(string $method, string $path, string $controller): void
    {
        if (isset($this->config[$path][$method])) {
            throw new RuntimeException("Path \"{$path}\" ({$method}) is already configured.");
        }

        $this->config[$path] = [$method => $controller];
    }

    /**
     * 
     * @param ServerRequestInterface $request
     * 
     * @return ResponseInterface|null
     * 
     * @throws RuntimeException If controller configuration in the container is missing
     */
    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        $controller_class = $this->resolvePath($request);
        if ($controller_class) {
            $response = $this->callController($controller_class);
        }

        return $response;
    }

    private function resolvePath(ServerRequestInterface $request): ?string
    {
        $controller_class = null;
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        if (isset($this->config[$path][$method])) {
            $controller_class = $this->config[$path][$method];
        }

        return $controller_class;
    }

    private function callController(string $controller_class, array $args = []): ResponseInterface
    {
        if (!$this->container->has($controller_class)) {
            throw new RuntimeException("\"{$controller_class}\" is not configured in the container");
        }

        /* @var $controller Controller */
        $controller = $this->container->get($controller_class);
        $response = $controller->run($args);

        return $response;
    }
}
