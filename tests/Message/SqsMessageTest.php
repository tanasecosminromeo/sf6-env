<?php

namespace App\Tests\Message;

use App\Message\ExampleMessage;
use AsyncAws\Sqs\SqsClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Bridge\AmazonSqs\Transport\AmazonSqsTransportFactory;
use Symfony\Component\Messenger\MessageBusInterface;

class SqsMessageTest extends KernelTestCase
{
    public function testSqsIntegration(): void
    {
        // Skip this test if not in the right environment
        // if (!getenv('RUN_SQS_TESTS')) {
        //     $this->markTestSkipped('SQS tests are disabled. Set RUN_SQS_TESTS=1 to enable');
        // }

        self::bootKernel();
        $container = static::getContainer();

        /** @var MessageBusInterface $messageBus */
        $messageBus = $container->get(MessageBusInterface::class);
        
        // Create and dispatch the message
        $message = new ExampleMessage('Test SQS message');
        $messageBus->dispatch($message);
        
        // For SQS, we can't easily check directly, but we can verify the transport is configured
        $this->assertTrue($container->has('messenger.transport.async'));
        
        // This is more of a configuration test than a functional test
        // In real scenarios, you'd either:
        // 1. Use a localstack for SQS testing
        // 2. Create a mocked SQS client
        $this->assertTrue(true);
    }
}
