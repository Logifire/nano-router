# naive-router

## Usage
**Basic**
```
    ...
    $router = new Router($container);
    $router->configurePath('GET', '/profiles/(?<uuid>[0-9a-f\-]{36})', StringController::class);
    $router->configurePath('GET', '/profiles/(?<id>\d+)', IntegerController::class);

    $response = $router->run($server_request);
    ... 
```
This package comes with PSR-15 RouterMiddleware
