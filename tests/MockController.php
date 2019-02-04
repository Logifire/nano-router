<?php
namespace NaiveRouter\Tests;

use NaiveRouter\Controller;
use NaiveRouter\RouterResult;
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
     * @var RouterResult
     */
    private $result;

    public const RESPONSE_BODY = 'MockControllerResponse';

    public function __construct(ServerRequestInterface $server_request, RouterResult $result)
    {

        $this->result = $result;
        $this->server_request = $server_request;
    }

    public function run(): ResponseInterface
    {
        $requested_path = (string) $this->server_request->getUri();
        $body = self::RESPONSE_BODY . " {$requested_path}";

        if ($this->result->hasInteger('user_id')) {
            $user_id = $this->result->getInetger('user_id');
            $body .= " - User ID: {$user_id}";
        }

        return new Response(200, [], $body);
    }
}
