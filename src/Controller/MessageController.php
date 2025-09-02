<?php

namespace App\Controller;

use App\Message\AgentMessage;
use App\Message\ExampleMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/messages', name: 'api_messages_')]
class MessageController extends AbstractController
{

    #[Route('/dispatch', name: 'dispatch_agent_question', methods: ['POST'])]
    #[OA\Tag(name: 'Message')]
    #[OA\RequestBody(
        required: true,
        description: 'Dispatch a question to our LangChain agent',
        content: new OA\JsonContent(
            required: ['query'],
            properties: [
                new OA\Property(property: 'query', type: 'string', example: 'Where is Bucharest located on the globe')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Message dispatched successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'Message dispatched successfully'),
                new OA\Property(property: 'time', type: 'string', format: 'date-time')
            ]
        )
    )]
    #[QA\Security(name: 'Bearer')]
    public function dispatchAgent(Request $request, MessageBusInterface $messageBus, LoggerInterface $consumerLogger): JsonResponse
    {
        $query = $request->request->get('query');

        if (!$query) {
            return $this->json(['error' => 'Query parameter is missing'], 400);
        }

        $message = new AgentMessage($query);
        $messageBus->dispatch($message);

        $consumerLogger->info('Dispatched '.$message);

        return $this->json([
            'status' => 'Message dispatched successfully',
            'query' => $query,
            'time' => new \DateTime(),
        ]);
    }

    #[Route('/dispatch/example', name: 'dispatch_example', methods: ['POST'])]
    #[OA\Tag(name: 'Message')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['content'],
            properties: [
                new OA\Property(property: 'content', type: 'string', example: 'Hello from the message controller. This will dispatch an ExampleMessage.')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Message dispatched successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'Message dispatched successfully'),
                new OA\Property(property: 'time', type: 'string', format: 'date-time')
            ]
        )
    )]
    #[QA\Security(name: 'Bearer')]
    public function dispatchExample(MessageBusInterface $messageBus): JsonResponse
    {
        // In a real implementation, you would get the content from the request body
        $message = new ExampleMessage('Hello from the message controller! ' . time());
        $messageBus->dispatch($message);
        return $this->json([
            'status' => 'Message dispatched successfully',
            'time' => new \DateTime(),
        ]);
    }
}
