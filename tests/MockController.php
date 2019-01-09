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
        return new Response(200, [], self::RESPONSE_BODY);
    }
}
