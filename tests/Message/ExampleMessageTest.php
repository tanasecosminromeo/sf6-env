<?php

namespace App\Tests\Message;

use App\Message\ExampleMessage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;

class ExampleMessageTest extends KernelTestCase
{
    public function testMessageIsDispatched(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Configure test transport if not already done in test config
        // You need to set up a test transport in config/packages/test/messenger.yaml

        /** @var MessageBusInterface $messageBus */
        $messageBus = $container->get(MessageBusInterface::class);
        
        // Create and dispatch the message
        $message = new ExampleMessage('Test dispatch');
        $messageBus->dispatch($message);
        
        /** @var InMemoryTransport $transport */
        $transport = $container->get('messenger.transport.async');
        
        // Check that the message was sent to the transport
        $this->assertCount(1, $transport->getSent());
        
        // Get the envelope and check it contains the expected message
        $envelope = $transport->getSent()[0];
        $this->assertEquals('Test dispatch', $envelope->getMessage()->getContent());
    }
}
