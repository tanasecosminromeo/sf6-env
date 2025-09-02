<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class GeocodingService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, ContainerBagInterface $params)
    {
        $this->httpClient = $httpClient;
        // Assuming your API key is stored in a .env file or services.yaml
        $this->apiKey = $params->get('geocoding_api_key');
    }

    /**
     * @param string $address The address to geocode.
     * @return array|null An array containing 'lat' and 'lng' or null on failure.
     */
    public function getCoordinates(string $address): ?array
    {
        // For security, you should escape or encode the address string.
        $encodedAddress = urlencode($address);
        
        $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?location=54.536272,-4.120361&radius=500&key={$this->apiKey}&input={$encodedAddress}";
        
        try {
            $response = $this->httpClient->request('GET', $url);
            
            // Check for a successful HTTP status code
            if ($response->getStatusCode() !== 200) {
                // Log or handle the error
                dump($response);
                return null;
            }

            $content = $response->toArray();

            dump($content, $this->apiKey);
            exit;
            
            // Assuming the API response structure is like:
            // {"results":[{"geometry":{"location":{"lat":...,"lng":...}}}]}
            if (isset($content['results'][0]['geometry']['location'])) {
                $location = $content['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                ];
            }
            
            return null;

        } catch (\Exception $e) {
            // Log the exception
            dump($e);
            return null;
        }
    }
}