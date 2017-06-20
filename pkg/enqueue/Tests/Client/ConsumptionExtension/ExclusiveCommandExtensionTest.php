<?php

namespace Enqueue\Tests\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\ExclusiveCommandExtension;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ExclusiveCommandExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ExclusiveCommandExtension::class);
    }

    public function testCouldBeConstructedWithQueueNameToProcessorNameMap()
    {
        new ExclusiveCommandExtension([]);

        new ExclusiveCommandExtension(['fooQueueName' => 'fooProcessorName']);
    }

    public function testShouldDoNothingIfMessageHasTopicPropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::PARAMETER_TOPIC_NAME, 'aTopic');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);

        $extension = new ExclusiveCommandExtension([
            'aFooQueueName' => 'aFooProcessorName',
        ]);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.topic_name' => 'aTopic',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfMessageHasProcessorNamePropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, 'aProcessor');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);

        $extension = new ExclusiveCommandExtension([
            'aFooQueueName' => 'aFooProcessorName',
        ]);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.processor_name' => 'aProcessor',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfMessageHasProcessorQueueNamePropertySetOnPreReceive()
    {
        $message = new NullMessage();
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, 'aProcessorQueueName');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);

        $extension = new ExclusiveCommandExtension([
            'aFooQueueName' => 'aFooProcessorName',
        ]);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.processor_queue_name' => 'aProcessorQueueName',
        ], $message->getProperties());
    }

    public function testShouldDoNothingIfCurrentQueueIsNotInTheMap()
    {
        $message = new NullMessage();
        $queue = new NullQueue('aBarQueueName');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);
        $context->setPsrQueue($queue);

        $extension = new ExclusiveCommandExtension([
            'aFooQueueName' => 'aFooProcessorName',
        ]);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([], $message->getProperties());
    }

    public function testShouldSetCommandPropertiesIfCurrentQueueInTheMap()
    {
        $message = new NullMessage();
        $queue = new NullQueue('aFooQueueName');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);
        $context->setPsrQueue($queue);
        $context->setLogger(new NullLogger());

        $extension = new ExclusiveCommandExtension([
            'aFooQueueName' => 'aFooProcessorName',
        ]);

        $extension->onPreReceived($context);

        self::assertNull($context->getResult());

        $this->assertEquals([
            'enqueue.topic_name' => '__command__',
            'enqueue.processor_queue_name' => 'aFooQueueName',
            'enqueue.processor_name' => 'aFooProcessorName',
        ], $message->getProperties());
    }
}
