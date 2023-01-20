<?php

namespace App\Queue;

interface HandlerInterface
{
    public function __invoke(Payload $payload);
}