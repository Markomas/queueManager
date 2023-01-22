<?php

namespace Markom\QueueManagerBundle\Queue;

interface HandlerInterface
{
    public function __invoke(Payload $payload);
}