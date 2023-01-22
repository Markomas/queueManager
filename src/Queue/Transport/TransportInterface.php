<?php

namespace Markom\QueueManagerBundle\Queue\Transport;

use Markom\QueueManagerBundle\Queue\Payload;

interface TransportInterface
{
    public function push(Payload $payload): void;
    public function reserve(string $pipe):?Payload;

    public function remove(Payload $payload): void;
    public function bury(Payload $payload): void;
}