<?php

namespace App\Queue\Transport;

use App\Queue\Payload;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use Symfony\Component\Serializer\SerializerInterface;

class BeanstalkTransport implements TransportInterface
{
    private array $jobsById = [];
    public function __construct(private Pheanstalk $pheanstalk, private readonly SerializerInterface $serializer){}

    public function push(Payload $payload): void
    {
        $this->pheanstalk->useTube($payload->getTube())->put(
            $this->serializer->serialize($payload, 'json')
        );
    }

    public function reserve(string $pipe): ?Payload
    {
        $job = $this->pheanstalk->watch($pipe)->reserveWithTimeout(0);

        if ($job) {
            $this->jobsById[$job->getId()] = $job;
            return $this->jobToPayload($job);
        }

        return null;
    }

    private function jobToPayload(Job $job): Payload
    {
        $payload = $this->serializer->deserialize($job->getData(), Payload::class, 'json');
        /** @var Payload $payload */
        $payload->setJobId($job->getId());
        $payload->setTouchCallback(function () use ($job) {
            $this->pheanstalk->touch($job);
        });

        return $payload;
    }

    public function remove(Payload $payload): void
    {
        $job = $this->getJob($payload);
        if($job) $this->pheanstalk->delete($job);
    }

    public function bury(Payload $payload): void
    {
        $job = $this->jobsById[$payload->getJobId()];
        if($job) $this->pheanstalk->bury($job);
    }

    /**
     * @param Payload $payload
     * @return Job|null
     */
    public function getJob(Payload $payload): ?Job
    {
        return $this->jobsById[$payload->getJobId()] ?? null;
    }
}