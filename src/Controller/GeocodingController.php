<?php

namespace App\Controller;

use App\Service\GeocodingService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GeocodingController extends AbstractController
{
    #[Route('/geocoding', name: 'app_geocoding')]
    public function place(Request $request, GeocodingService $service, LoggerInterface $consumerLogger): Response
    {

        $location = $request->get('location');

        if (strlen($location) < 2){
            $consumerLogger->warning('Location is too short', ['location' => $location]);
            return $this->json(['error' => 'Location is too short'], Response::HTTP_BAD_REQUEST);
        }

        $result = $service->getCoordinates($location);

        $consumerLogger->info('Geocoding request', ['location' => $location, 'result' => $result, 'requested_by' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

        return $this->json([
            'location' => $location,
            'result' => $result
        ]);
        
    }
}
