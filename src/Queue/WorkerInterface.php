<?php

namespace Markom\QueueManagerBundle\Queue;

interface WorkerInterface
{
    public function run(string $tube): void;
}