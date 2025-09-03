<?php
namespace App\MessageHandler;

use App\Message\AgentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class AgentMessageHandler
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(LoggerInterface $consumerLogger, EntityManagerInterface $entityManager)
    {
        $this->logger = $consumerLogger;
        $this->em = $entityManager;
    }

    public function __invoke(AgentMessage $message)
    {
        if ($message->getType() !== AgentMessage::TO_LLM){
            $this->logger->warning(sprintf('This consumer doesn\'t support message type: %s', $message));
            return;
        }

        $content = $message->getContent();

        $this->logger->info('Processing message: ' . $content);
        
        // ... rest of your processing logic
        
        $this->logger->info('Message processed successfully');
    }
}