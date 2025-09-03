<?php

namespace App\Controller;

use App\Entity\Job;
use App\Message\AgentMessage;
use App\Message\ExampleMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/messages', name: 'api_messages_')]
class MessageController extends AbstractController
{

    #[Route('/dispatch', name: 'dispatch_agent_question', methods: ['POST'])]
        #[OA\Tag(name: 'Message')]
    #[OA\RequestBody(
        required: true,
        description: 'Dispatch a question to our LangChain agent',
        content: new OA\MediaType(
            mediaType: 'application/x-www-form-urlencoded',
            schema: new OA\Schema(
                required: ['query'],
                properties: [
                    new OA\Property(
                        property: 'query', 
                        type: 'string', 
                        example: 'Where is Bucharest located on the globe',
                        minLength: 2
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Message already processed (existing job found)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'Message already processed'),
                new OA\Property(property: 'query', type: 'string', example: 'Where is Bucharest located on the globe'),
                new OA\Property(property: 'result', type: 'object', example: [
                    'place_id' => 'ChIJKRjik9r_rUARIP-zcCwWpkI',
                    'description' => 'Bucharest, Romania',
                    'lat' => 44.4267674,
                    'lng' => 26.1025384,
                    'fresh' => false
                ]),
                new OA\Property(property: 'time', type: 'string', format: 'date-time', example: '2025-09-03T08:15:30+00:00'),
                new OA\Property(property: 'jobTime', type: 'string', format: 'date-time', example: '2025-09-03T08:15:30+00:00')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Message dispatched successfully (new job created)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'Message dispatched successfully'),
                new OA\Property(property: 'query', type: 'string', example: 'Where is Bucharest located on the globe'),
                new OA\Property(property: 'time', type: 'string', format: 'date-time', example: '2025-09-03T08:15:30+00:00'),
                new OA\Property(property: 'jobTime', type: 'string', format: 'date-time', example: '2025-09-03T08:15:30+00:00')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Query parameter is missing or too short')
            ]
        )
    )]
    public function dispatchAgent(Request $request, MessageBusInterface $messageBus, LoggerInterface $consumerLogger, EntityManagerInterface $em): JsonResponse
    {
        $query = $request->request->get('query');

        if (!$query || strlen($query) < 2){
            return $this->json(['error' => 'Query parameter is missing or too short'], 400);
        }

        $jobRepository = $em->getRepository(Job::class);
        $job = $jobRepository->findOneBy(['original_query' => $query]);

        if ($job) {
            $consumerLogger->info(sprintf('Job already exists: %s', $job));

            return $this->json([
                'status' => 'Message already processed',
                'query' => $query,
                'result' => $job->getResult(),
                'time' => new \DateTime(),
                'jobTime' => $job->getCreatedAt()
            ], Response::HTTP_OK);
        }

        $message = new AgentMessage($query);
        $messageBus->dispatch($message);

        $job = new Job();
        $job->setOriginalQuery($query);
        $em->persist($job);
        $em->flush();

        $consumerLogger->info('Dispatched '.$job); //By implementing __toString method on the Job entity - easy concatenation is available (see alternative with sprintf above - still as string)

        return $this->json([
            'status' => 'Message dispatched successfully',
            'query' => $query,
            'time' => new \DateTime(),
            'jobTime' => $job->getCreatedAt()
        ], Response::HTTP_CREATED);
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
