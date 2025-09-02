<?php

namespace App\MessageHandler;

use App\Message\ExampleMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExampleMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ExampleMessage $message)
    {
        $this->logger->info('Processing message: ' . $message->getContent());
        
        // Process your message here
        // For example, perform some operations, call external APIs, etc.
        
        // Simulate work
        sleep(2);
        
        $this->logger->info('Message processed successfully');
    }
}
