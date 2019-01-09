<?php
namespace NaiveRouter\Tests;

use NaiveRouter\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;

class MockController implements Controller
{

    public const RESPONSE_BODY = 'MockControllerResponse';

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], self::RESPONSE_BODY);
    }
}
