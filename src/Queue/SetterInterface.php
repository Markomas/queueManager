<?php

namespace Markom\QueueManagerBundle\Queue;

interface SetterInterface
{
    public function set(Payload $param): void;
}