<?php
namespace NaiveRouter\Tests;

use NaiveRouter\Controller;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class MockController implements Controller
{

    public const RESPONSE_BODY = 'MockControllerResponse';

    public function run(array $args = []): ResponseInterface
    {
        $requested_path = $args[0];
        $body = self::RESPONSE_BODY . " {$requested_path}";
        return new Response(200, [], $body);
    }
}
