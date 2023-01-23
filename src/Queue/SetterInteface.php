<?php

namespace Markom\QueueManagerBundle\Queue;

interface SetterInteface
{
    public function set(Payload $param): void;
}