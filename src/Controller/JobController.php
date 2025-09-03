<?php

namespace App\Controller;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/jobs', name: 'api_jobs_')]
#[OA\Tag(name: 'Jobs')]
final class JobController extends AbstractController
{   
    #[Route('/search', methods: ['GET'], name: 'job_search')]
    #[OA\Parameter(
        name: 'query',
        in: 'query',
        required: true,
        description: 'The original query to search for',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns jobs matching the search query',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 3),
                new OA\Property(property: 'query', type: 'string', example: 'bucharest'),
                new OA\Property(property: 'jobs', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'original_query', type: 'string', example: 'Where is Bucharest?'),
                        new OA\Property(property: 'location', type: 'string', example: 'Bucharest'),
                        new OA\Property(property: 'query', type: 'string', example: 'Bucharest coordinates'),
                        new OA\Property(property: 'result', type: 'object'),
                        new OA\Property(property: 'messageId', type: 'string'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'sentAt', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'resolvedAt', type: 'string', format: 'date-time')
                    ]
                ))
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Missing query parameter',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Missing query parameter')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function searchJob(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = $request->query->get('query');
        
        if (!$query) {
            return $this->json(['error' => 'Missing query parameter'], Response::HTTP_BAD_REQUEST);
        }
    
        // Changed to use getResult() (0 or more) instead of getOneOrNullResult() (only for 1 result)
        $jobs = $em->getRepository(Job::class)->createQueryBuilder('j')
            ->where('LOWER(j.original_query) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->getQuery()
            ->getResult();
    
        $jobsData = array_map(function($job) {
            return [
                'id' => $job->getId(),
                'original_query' => $job->getOriginalQuery(),
                'location' => $job->getLocation(),
                'query' => $job->getQuery(),
                'result' => $job->getResult(),
                'messageId' => $job->getMessageId(),
                'createdAt' => $job->getCreatedAt(),
                'sentAt' => $job->getSentAt(),
                'resolvedAt' => $job->getResolvedAt()
            ];
        }, $jobs);
        
        // Return all matching jobs with count
        return $this->json([
            'total' => count($jobs),
            'query' => $query,
            'jobs' => $jobsData
        ]);
    }

    #[Route('', methods: ['GET'], name: 'job_list')]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'Page number (default: 1)',
        schema: new OA\Schema(type: 'integer', default: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        required: false,
        description: 'Number of jobs per page (default: 10, max: 100)',
        schema: new OA\Schema(type: 'integer', default: 10, maximum: 100)
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        required: false,
        description: 'Sort field (default: id)',
        schema: new OA\Schema(type: 'string', default: 'id', enum: ['id', 'createdAt', 'resolvedAt'])
    )]
    #[OA\Parameter(
        name: 'order',
        in: 'query',
        required: false,
        description: 'Sort order (default: DESC)',
        schema: new OA\Schema(type: 'string', default: 'DESC', enum: ['ASC', 'DESC'])
    )]
    #[OA\Response(
        response: 200,
        description: 'List of jobs',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'page', type: 'integer', example: 1),
                new OA\Property(property: 'limit', type: 'integer', example: 10),
                new OA\Property(property: 'total', type: 'integer', example: 100),
                new OA\Property(property: 'jobs', type: 'array', items: new OA\Items(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'original_query', type: 'string', example: 'Where is Bucharest?'),
                        new OA\Property(property: 'location', type: 'string', example: 'Bucharest'),
                        new OA\Property(property: 'result', type: 'object', example: [
                            'place_id' => 'ChIJKRjik9r_rUARIP-zcCwWpkI',
                            'description' => 'Bucharest, Romania',
                            'lat' => 44.4267674,
                            'lng' => 26.1025384
                        ]),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-09-03T08:15:30+00:00')
                    ]
                ))
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function listJobs(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;
        
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');
        
        // Validate sort field
        if (!in_array($sort, ['id', 'createdAt', 'resolvedAt'])) {
            $sort = 'id';
        }
        
        // Validate order
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $jobRepo = $em->getRepository(Job::class);
        $jobs = $jobRepo->findBy([], [$sort => $order], $limit, $offset);
        $total = $jobRepo->count([]);

        $data = array_map(function($job) {
            return [
                'id' => $job->getId(),
                'original_query' => $job->getOriginalQuery(),
                'location' => $job->getLocation(),
                'query' => $job->getQuery(),
                'result' => $job->getResult(),
                'messageId' => $job->getMessageId(),
                'createdAt' => $job->getCreatedAt(),
                'sentAt' => $job->getSentAt(),
                'resolvedAt' => $job->getResolvedAt()
            ];
        }, $jobs);

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'jobs' => $data,
        ]);
    }


    #[Route('/{id}', methods: ['GET'], name: 'job_get')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'The job ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns job details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'original_query', type: 'string', example: 'Where is Bucharest?'),
                new OA\Property(property: 'location', type: 'string', example: 'Bucharest'),
                new OA\Property(property: 'query', type: 'string', example: 'Bucharest coordinates'),
                new OA\Property(property: 'result', type: 'object', example: [
                    'place_id' => 'ChIJKRjik9r_rUARIP-zcCwWpkI',
                    'description' => 'Bucharest, Romania',
                    'lat' => 44.4267674,
                    'lng' => 26.1025384,
                    'fresh' => false
                ]),
                new OA\Property(property: 'messageId', type: 'string', example: '96b91f3e-c9a5-4e35-97a7-c59e22326b04'),
                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-09-03T08:15:30+00:00'),
                new OA\Property(property: 'sentAt', type: 'string', format: 'date-time', example: '2025-09-03T08:15:28+00:00'),
                new OA\Property(property: 'resolvedAt', type: 'string', format: 'date-time', example: '2025-09-03T08:15:35+00:00')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Job not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Job not found')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function getJob(?Job $job): JsonResponse
    {
        if (!$job) {
            return $this->json(['error' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $job->getId(),
            'original_query' => $job->getOriginalQuery(),
            'location' => $job->getLocation(),
            'query' => $job->getQuery(),
            'result' => $job->getResult(),
            'messageId' => $job->getMessageId(),
            'createdAt' => $job->getCreatedAt(),
            'sentAt' => $job->getSentAt(),
            'resolvedAt' => $job->getResolvedAt()
        ]);
    }
}