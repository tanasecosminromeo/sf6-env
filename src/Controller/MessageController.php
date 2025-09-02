<?php

namespace App\Controller;

use App\Message\ExampleMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/messages', name: 'api_messages_')]
class MessageController extends AbstractController
{
    #[Route('/dispatch', name: 'dispatch', methods: ['POST'])]
    public function dispatch(MessageBusInterface $messageBus): JsonResponse
    {
        $message = new ExampleMessage('Hello from the message controller! ' . time());
        $messageBus->dispatch($message);
        
        return $this->json([
            'status' => 'Message dispatched successfully',
            'time' => new \DateTime(),
        ]);
    }
}
