<?php

namespace Markom\QueueManagerBundle\Queue;

use Symfony\Component\Serializer\Annotation\Ignore;

class Payload
{
    #[Ignore]
    private string|int $jobId;
    #[Ignore]
    private \Closure $touchCallback;

    #[Ignore]
    private ?object $message;

    #[Ignore]
    private ?string $key = null;

    public \DateTimeImmutable $packageDate;

    public function __construct(
        private string $tube,
        private string $action,
        private array  $payload
    )
    {
        $this->touchCallback = function () {
        };
    }

    /**
     * @return int|string
     */
    public function getJobId(): int|string
    {
        return $this->jobId;
    }

    /**
     * @param int|string $jobId
     */
    public function setJobId(int|string $jobId): void
    {
        $this->jobId = $jobId;
    }

    /**
     * @return \Closure
     */
    public function getTouchCallback(): \Closure
    {
        return $this->touchCallback;
    }

    /**
     * @param \Closure $touchCallback
     */
    public function setTouchCallback(\Closure $touchCallback): void
    {
        $this->touchCallback = $touchCallback;
    }

    /**
     * @return string
     */
    public function getTube(): string
    {
        return $this->tube;
    }

    /**
     * @param string $tube
     */
    public function setTube(string $tube): void
    {
        $this->tube = $tube;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     */
    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    /**
     * @return object|null
     */
    public function getMessage(): ?object
    {
        return $this->message;
    }

    /**
     * @param object|null $message
     */
    public function setMessage(?object $message): void
    {
        $this->message = $message;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getPackageDate(): \DateTimeImmutable
    {
        return $this->packageDate;
    }

    /**
     * @param \DateTimeImmutable $packageDate
     */
    public function setPackageDate(\DateTimeImmutable $packageDate): void
    {
        $this->packageDate = $packageDate;
    }
}