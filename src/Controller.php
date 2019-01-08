<?php
namespace NaiveRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Controller
{

    public function run(ServerRequestInterface $request): ResponseInterface;
}
