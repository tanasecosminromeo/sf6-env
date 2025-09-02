<?php

namespace App\Tests\MessageHandler;

use App\Message\ExampleMessage;
use App\MessageHandler\ExampleMessageHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ExampleMessageHandlerTest extends TestCase
{
    public function testItHandlesExampleMessage(): void
    {
        // Create a mock for the logger
        $logger = $this->createMock(LoggerInterface::class);
        
        // Set expectations for the logger - in PHPUnit 12, we no longer use withConsecutive
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->callback(function ($message) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    return str_contains($message, 'Processing message:');
                } else {
                    return str_contains($message, 'Message processed successfully');
                }
            }));
        
        // Create the handler with the mocked logger
        $handler = new ExampleMessageHandler($logger);
        
        // Create a message
        $message = new ExampleMessage('Test message content');
        
        // Invoke the handler
        $handler($message);
        
        // If we reach here without exceptions and the logger expectations were met, the test passes
        $this->assertTrue(true);
    }
}
