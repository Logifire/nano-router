<?php

namespace NanoRouter;

use Psr\Http\Message\ResponseInterface;

interface Controller
{

    public function buildResponse(): ResponseInterface;
}
