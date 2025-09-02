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


        return $this->json([
            'location' => $location,
            'result' => $service->getCoordinates($location)
        ]);
        
    }
}
