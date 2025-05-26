<?php
declare(strict_types=1);

namespace NanoRouter;

use Psr\Http\Message\ResponseInterface;

interface Controller
{
    public function buildResponse(): ResponseInterface;
}
