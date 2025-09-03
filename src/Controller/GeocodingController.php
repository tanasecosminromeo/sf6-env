<?php

namespace App\Controller;

use App\Entity\Job;
use App\Service\DateHelpers;
use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GeocodingController extends AbstractController
{
    #[Route('/geocoding', name: 'app_geocoding')]
    public function place(Request $request, GeocodingService $service, LoggerInterface $consumerLogger, EntityManagerInterface $em, DateHelpers $dateHelpers): Response
    {        
        $location = $request->request->get('location');

        if (strlen($location) < 2){
            $consumerLogger->warning('Location is too short', ['location' => $location]);
            return $this->json(['error' => 'Location is too short'], Response::HTTP_BAD_REQUEST);
        }

        $query = $request->request->get('query');
        $original_message = $request->request->get('original_message');
        $messageId = $request->request->get('messageId');
        $sentAt = $request->request->get('sentAt');
        $sentAtDatetime = $sentAt ? $dateHelpers->millisecondsToDateTime($sentAt) : null;

        $result = $service->getCoordinates($location);

        /** @var JobRepository $jobRepository */
        $jobRepository = $em->getRepository(Job::class);

        if (!empty($original_message)){
            // Update or create job
            $job = $jobRepository->updateOrCreateJob(
                $original_message,
                $location,
                $result,
                $query,
                $messageId,
                $sentAtDatetime
            );
        }

        $consumerLogger->info('Geocoding request', [
            'location' => $location, 
            'result' => $result, 
            'query' => $query,
            'original_message' => $original_message,
            'messageId' => $messageId,
            'sentAt' => $sentAt,
            'sentAtDatetime' => $sentAtDatetime,
            'currentTime' => new \DateTimeImmutable(),
            'requested_by' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'jobId' => !empty($original_message) ? $job->getId() : null
        ]);


        return $this->json([
            'location' => $location,
            'result' => $result
        ]);
        
    }
}
