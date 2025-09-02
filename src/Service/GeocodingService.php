<?php

namespace App\Service;

use PHPUnit\Util\Filesystem;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class GeocodingService
{
    private $httpClient;
    private $apiKey;
    private FilesystemAdapter $cache;

    public function __construct(HttpClientInterface $httpClient, ContainerBagInterface $params)
    {
        $this->httpClient = $httpClient;
        // Assuming your API key is stored in a .env file or services.yaml
        $this->apiKey = $params->get('geocoding_api_key');
        $this->cache = new FilesystemAdapter(
            'app.geocode',
            0,
            '/code/var/cache/geocode'
            );
    }

    /**
     * @param string $address The address to geocode.
     * @return array|null An array containing 'lat' and 'lng' or null on failure.
     */
    public function getCoordinates(string $address): ?array
    {
        $cacheKey = 'geocode_' . md5($address);

        $cache = $this->cache->getItem($cacheKey);

        if ($cache->isHit()) {
            return $cache->get();
        }

        // For security, you should escape or encode the address string.
        $encodedAddress = urlencode($address);
        
        $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?location=54.536272,-4.120361&radius=500&key={$this->apiKey}&input={$encodedAddress}";
        
        try {
            $response = $this->httpClient->request('GET', $url);
            
            // Check for a successful HTTP status code
            if ($response->getStatusCode() !== 200) {
                // Log or handle the error
                return null;
            }

            $content = $response->toArray();

            // Get first prediction
            $prediction = $content['predictions'][0] ?? null;

            if (empty($prediction)) {
                return null;
            }

            //get lat lon for place_id

            $placeId = $prediction['place_id'] ?? null;

            if ($placeId) {
                $detailsUrl = "https://maps.googleapis.com/maps/api/place/details/json?placeid={$placeId}&key={$this->apiKey}";

                try {
                    $detailsResponse = $this->httpClient->request('GET', $detailsUrl);

                    if ($detailsResponse->getStatusCode() === 200) {
                        $detailsContent = $detailsResponse->toArray();

                        // Assuming the API response structure is like:
                        // {"results":[{"geometry":{"location":{"lat":...,"lng":...}}}]}
                        if (isset($detailsContent['result']['geometry']['location'])) {
                            $location = $detailsContent['result']['geometry']['location'];

                            $data = [
                                'place_id' => $placeId,
                                'description' => $prediction['description'] ?? null,
                                'lat' => $location['lat'],
                                'lng' => $location['lng'],
                            ];

                            $cache->set(array_merge($data, ['fresh' => false]));
                            $this->cache->save($cache);

                            $data['fresh'] = true;
                            return $data;
                        }
                    }
                } catch (\Exception $e) {
                    // Log the exception
                    dump($e);
                    return null;
                }
            }
            
            return null;

        } catch (\Exception $e) {
            // Log the exception
            dump($e);
            return null;
        }
    }
}