<?php
declare(strict_types=1);

namespace Markom\QueueManagerBundle;

use Markom\QueueManagerBundle\DependencyInjection\QueueManagerExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class QueueManagerBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new QueueManagerExtension();
    }
}
