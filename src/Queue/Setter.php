<?php

namespace Markom\QueueManagerBundle\Queue;

use Markom\QueueManagerBundle\Queue\Transport\TransportInterface;

readonly class Setter implements SetterInterface
{
    public function __construct(private TransportInterface $transport){}

    public function set(Payload $param): void
    {
        $param->setPackageDate(new \DateTimeImmutable('now'));
        $this->transport->push($param);
    }
}