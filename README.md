![](https://github.com/logifire/nano-router/workflows/Router%20tests/badge.svg)

# nano-router

## Usage

**Basic**

```
    // Controller implementation
    class StringController implements Controller {
        public function run(): ResponseInterface {
            ...
        }
    }

    ...
    // Router configuration
    $router = new Router();
    $router->configurePath(Router::METHOD_GET, '/profiles/(?<uuid>[0-9a-f\-]{36})', StringController::class);
    $router->configurePath(Router::METHOD_GET, '/profiles/(?<id>\d+)', IntegerController::class);

    ...
    // Request handling
    $router_result = $router->processRequest($server_request);

    if ($router_result !== null) {
        $controller_name = $router_result->getControllerName();
        $path_result = $router_result->getPathResult();
        $query_result = $router_result->getQueryResult();

        $controller = new $controller_name({dependencies});

        $psr7_response = $controller->buildResponse();
    }
    ... 

```

This package comes with PSR-15 RouterMiddleware
