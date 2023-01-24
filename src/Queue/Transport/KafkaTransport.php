<?php
declare(strict_types=1);

namespace Markom\QueueManagerBundle\Queue\Transport;

use Jobcloud\Kafka\Consumer\KafkaConsumerInterface;
use Jobcloud\Kafka\Message\KafkaProducerMessage;
use Jobcloud\Kafka\Producer\KafkaProducerBuilder;
use Jobcloud\Kafka\Producer\KafkaProducerInterface;
use Markom\QueueManagerBundle\Queue\Kafka\CustomKafkaConsumerBuilder;
use Markom\QueueManagerBundle\Queue\Payload;
use Psr\Log\LoggerInterface;
use RdKafka\Conf;
use RdKafka\Consumer as RdKafkaLowLevelConsumer;
use RdKafka\KafkaConsumer;
use RdKafka\KafkaConsumer as RdKafkaHighLevelConsumer;
use RdKafka\Metadata;
use Symfony\Component\Serializer\SerializerInterface;

class KafkaTransport implements TransportInterface
{
    private KafkaProducerInterface $producer;
    private string $broker;
    private array $conf = [];

    /**
     * @var KafkaConsumerInterface[]
     */
    private array $consumer;
    private ?Metadata $metadata = null;
    private RdKafkaLowLevelConsumer|RdKafkaHighLevelConsumer $rdConsumerKafka;

    public function __construct(private string $dsn, private readonly SerializerInterface $serializer, private readonly LoggerInterface $logger) {
        $this->loadDsn($this->dsn);
        $this->buildProducer();
    }

    public function __destruct()
    {
        $this->producer->flush(20000);
    }


    public function push(Payload $payload): void
    {
        $message = KafkaProducerMessage::create($payload->getTube(), 0)
            ->withKey($payload->getKey())
            ->withBody($this->serializer->serialize($payload, 'json'));

        $this->producer->produce($message);
    }

    public function reserve(string $pipe): ?Payload
    {
        $topics = $this->loadExistingTopics($pipe);

        $code = md5(json_encode($topics));
        if (!isset($this->consumer[$code])) {
            dump($code, 'subscribe to topics');
            $this->consumer[$code] = $this->buildConsumer($topics);
            $this->consumer[$code]->subscribe();
        }

        dump($code, 'consuming topics');
        $message = $this->consumer[$code]->consume(10000);
        $payload = $this->serializer->deserialize($message->getBody(), Payload::class, 'json');/** @var Payload $payload */;
        $payload->setMessage($message);
        $payload->setTube($message->getTopicName());
        $this->consumer[$message->getTopicName()] = $this->consumer[$code];

        $payload->setTouchCallback(function () use ($code, $topics) {
            $this->rdConsumerKafka->poll(1000);
        });
        dump($payload->getPayload());

        return $payload;
    }

    public function remove(Payload $payload): void
    {
        $this->consumer[$payload->getTube()]->commit($payload->getMessage());
    }

    public function bury(Payload $payload): void
    {
        $originalPipe = $payload->getTube();

        $this->push($payload);
        $payload->setTube($payload->getTube() . '_failed');
        $this->push($payload);
        $this->consumer[$originalPipe]->commit($payload->getMessage());
    }

    private function buildProducer()
    {
        $this->producer = KafkaProducerBuilder::create()
            ->withAdditionalBroker($this->broker)
            ->withAdditionalConfig([
                'compression.codec' => 'gzip'
            ])
            ->withLogCallback(function ($kafka, $errId, $msg) {
                $this->logger->error($msg);
            })
            ->build();
    }

    private function buildConsumer(array $topics): KafkaConsumerInterface
    {
        //TODO: fix this mess
        $builder = new CustomKafkaConsumerBuilder();
        $consumerBuilder = $builder->create()
            ->withConsumerGroup($this->conf['group.id'])
            ->withConsumerType(CustomKafkaConsumerBuilder::CONSUMER_TYPE_LOW_LEVEL)
            ->withAdditionalBroker($this->broker);
        foreach ($topics as $topic) {
            $consumerBuilder = $consumerBuilder->withAdditionalSubscription($topic);
        }
        $consumer = $consumerBuilder->build();
        $this->rdConsumerKafka = $consumerBuilder->getRdKafkaConsumer();

        return $consumer;
    }

    private function loadDsn(string $dsn)
    {
        $urlParts = parse_url($dsn);
        $this->broker = ($urlParts['host'] ?? 'localhost') . ':' . ($urlParts['port'] ?? '9092');
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $query);
            foreach ($query as $key => $value) {
                $key = str_replace('_', '.', $key);
                $this->conf[$key] = $value;
            }
        }
        if(empty($this->conf['group.id'])) {
            $this->conf['group.id'] = uniqid();
        }
    }

    private function loadExistingTopics(string $name): array
    {
        $topics = [];

        if(is_null($this->metadata)) {
            $conf = new Conf();
            $conf->set('metadata.broker.list', $this->broker);
            $conf->set('group.id', $this->conf['group.id'] . '_metadata');

            $conf->setErrorCb(function ($kafka, $err, $reason) {
                $this->logger->log($err, $reason);
            });

            $consumer = new KafkaConsumer($conf);
            //$consumer = $this->rdConsumerKafka;
            $this->metadata = $consumer->getMetadata(true, null, 1000);
        }
        foreach ($this->metadata->getTopics() as $topic) {
            if(fnmatch($name, $topic->getTopic())) {
                $topics[] = $topic->getTopic();
            }
        }

        return $topics;
    }
}
