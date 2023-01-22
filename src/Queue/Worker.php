<?php

namespace Markom\QueueManagerBundle\Queue;

use Markom\QueueManagerBundle\Queue\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

readonly class Worker
{
    public function __construct(private TransportInterface $transport, private array $queueActions, private LoggerInterface $logger) {}
    public function run(string $tube): void
    {
        $this->logger->debug(sprintf('Running tube: %s', $tube));
        $payload = $this->transport->reserve($tube);
        if(!$payload) return;
        if(!isset($this->queueActions[$payload->getAction()])) {
            $this->logger->warning(
                sprintf('Action not found: %s', $payload->getAction()),
                ['tube' => $tube, 'step' => 'verify']
            );
            $this->transport->bury($payload);
            return;
        }

        $handler = $this->queueActions[$payload->getAction()];

        if(!$handler instanceof HandlerInterface) {
            $this->logger->warning(
                sprintf('Invalid action found: %s', $payload->getAction()),
                ['tube' => $tube, 'step' => 'verify']
            );
            $this->transport->bury($payload);
            return;
        }

        try {
            $handler($payload);
        } catch (\Exception $e) {
            $this->transport->bury($payload);
            $this->logger->error(
                sprintf('%s: %s', $e->getMessage(), $e->getTraceAsString()),
                ['tube' => $tube, 'step' => 'invoke']
            );
            throw $e;
        }

        $this->transport->remove($payload);
    }
}