<?php
namespace NaiveRouter;

use Psr\Http\Message\ResponseInterface;

interface Controller
{

    public function run(array $args = []): ResponseInterface;
}
