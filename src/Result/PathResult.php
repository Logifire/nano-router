<?php

namespace NanoRouter\Result;

class PathResult extends RequestResult
{

    public function __construct(array $matches)
    {

        $this->matches = $matches;
    }
}
