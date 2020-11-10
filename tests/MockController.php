<?php

namespace NanoRouter\Tests;

use NanoRouter\Controller;
use NanoRouter\Result\PathResult;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MockController implements Controller
{

    /**
     * @var ServerRequestInterface
     */
    private $server_request;

    /**
     * @var PathResult
     */
    private $path_result;

    public const RESPONSE_BODY = 'MockControllerResponse';

    public function __construct(ServerRequestInterface $server_request,
        PathResult $path_result)
    {

        $this->path_result = $path_result;
        $this->server_request = $server_request;
    }

    public function buildResponse(): ResponseInterface
    {
        $requested_path = (string) $this->server_request->getUri();
        $body = self::RESPONSE_BODY . " {$requested_path}";

        if ($this->path_result->hasInteger('user_id')) {
            $user_id = $this->path_result->getInteger('user_id');
            $body .= " - User ID: {$user_id}";
        }

        return new Response(200, [], $body);
    }
}
