<?php

namespace NanoRouter;

use NanoRouter\Exception\RouterException;
use NanoRouter\Result\PathResult;
use NanoRouter\Result\QueryResult;
use NanoRouter\Result\RouterResult;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods Request methods
 * @link https://developer.mozilla.org/en-US/docs/Glossary/idempotent Idempotent
 */
class Router
{

    private RouterKernel $router_kernel;

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const MEHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_HEAD = 'HEAD';

    public function __construct()
    {
        $this->router_kernel = new RouterKernel();
    }

    /**
     * For read requests. Idempotent.
     */
    public function mapGet(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_GET, $path, $controller_name);
    }

    /**
     * For create request.
     */
    public function mapPost(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_POST, $path, $controller_name);
    }

    /**
     * For update requests. Full payload required. Idempotent.
     */
    public function mapPut(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_PUT, $path, $controller_name);
    }

    /**
     * For update requests. Only changed parameters required.
     */
    public function mapPatch(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_PATCH, $path, $controller_name);
    }

    /**
     * For delete requests. Idempotent.
     */
    public function mapDelete(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_DELETE, $path, $controller_name);
    }

    /**
     * Lists supported request methods for a given URL. Idempotent.
     * 
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
     */
    public function mapOptions(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_OPTIONS, $path, $controller_name);
    }

    /**
     * Like a GET request, but without body payload - only header responses.
     * Idempotent.
     */
    public function mapHead(string $path, string $controller_name): void
    {
        $this->configurePath(self::METHOD_HEAD, $path, $controller_name);
    }

    /**
     * @param string $method HTTP method e.g. GET. See self::METHOD_* constants.
     * @param string $path Request path e.g. /profiles/(?<uuid>[0-9a-f\-]{36})
     * @param string $controller_name The fully qualified class name
     * 
     * @return void
     * 
     * @throws RouterException If path is already configured, or unsupported method
     */
    public function configurePath(string $method, string $path,
        string $controller_name): void
    {
        $this->router_kernel->configurePath($method, $path, $controller_name);
    }

    /**
     * @param ServerRequestInterface $request
     * @return RouterResult|null
     */
    public function processRequest(ServerRequestInterface $request): ?RouterResult
    {
        $router_result = null;
        $method = $request->getMethod();
        $uri = $request->getUri();
        $requested_path = $uri->getPath();
        $query = $uri->getQuery();

        $lookup_result = $this->router_kernel->lookup($method, $requested_path);

        ["controller_name" => $controller_name, "group_name" => $path_keys] = $lookup_result;

        if ($controller_name) {
            $path_result = new PathResult($path_keys);
            $query_result = new QueryResult($query);
            $router_result = new RouterResult($controller_name, $path_result,
                $query_result);
        }
        return $router_result;
    }
}
