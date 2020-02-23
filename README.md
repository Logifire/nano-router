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
    $router->configurePath('GET', '/profiles/(?<uuid>[0-9a-f\-]{36})', StringController::class);
    $router->configurePath('GET', '/profiles/(?<id>\d+)', IntegerController::class);

    ...
    // Request handling
    $router_result = $router->run($server_request);

    if ($router_result !== null) {
        $controller_name = $router_result->getController();
        $controller = new $controller_name({dependencies});
        $psr7_response = $controller->run();
    }
    ... 

```

This package comes with PSR-15 RouterMiddleware
