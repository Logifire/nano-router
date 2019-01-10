<?php
namespace NaiveRouter;

use NaiveFramework\Controller;
use NaiveRouter\Exception\RouterException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    public const METHODS = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ];

    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;
    }

    /**
     * @throws RouterException If path is already configured, or unsupported method
     */
    public function configurePath(string $method, string $path, string $controller): void
    {
        if (!in_array($method, self::METHODS)) {
            throw new RouterException("Method not supported: {$method}");
        }

        if (isset($this->config[$path][$method])) {
            throw new RouterException("Path \"{$path}\" ({$method}) is already configured.");
        }

        if (@preg_match("#{$path}#", '') === false) {
            throw new RouterException("Invalid path: {$path}");
        }

        $this->config[$path] = [$method => $controller];
    }

    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        return $this->resolvePath($request);
    }

    private function resolvePath(ServerRequestInterface $request): ?ResponseInterface
    {
        $response = null;
        $path = rtrim($request->getUri()->getPath(), '/');
        $method = $request->getMethod();

        foreach ($this->config as $pattern => $method_controller) {
            if (@preg_match("#^{$pattern}$#iu", $path, $matches) === 1) {
                if (isset($method_controller[$method])) {
                    $response = $this->callController($method_controller[$method], $matches);
                    break;
                }
            }
        }

        return $response;
    }

    private function callController(string $controller_class, array $args = []): ResponseInterface
    {
        if (!$this->container->has($controller_class)) {
            throw new RouterException("\"{$controller_class}\" is not configured in the container");
        }

        /* @var $controller Controller */
        $controller = $this->container->get($controller_class);
        $response = $controller->run($args);

        return $response;
    }
}
