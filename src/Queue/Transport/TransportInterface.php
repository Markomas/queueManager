<?php

namespace App\Queue\Transport;

use App\Queue\Payload;

interface TransportInterface
{
    public function push(Payload $payload): void;
    public function reserve(string $pipe):?Payload;

    public function remove(Payload $payload): void;
    public function bury(Payload $payload): void;
}