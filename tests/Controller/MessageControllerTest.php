<?php

namespace App\Tests\Controller;

use App\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Doctrine\ORM\EntityManagerInterface;

class MessageControllerTest extends WebTestCase
{
    private function getJwtToken($client, $username = 'crtanase', $password = 'mysecretpassword2025'): string
    {
        $client->request(
            'POST', 
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => $username, 'password' => $password])
        );
        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['token'] ?? '';
    }
    
    public function testDispatchEndpointCreatesNewJob(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);

        $query = 'Where is ' . uniqid() . ' located?'; // Ensure unique query for new job

        // Make a request to the dispatch endpoint
        $client->request(
            'POST', 
            '/api/messages/dispatch', 
            ['query' => $query], 
            [], 
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
            ]
        );

        // Assert that the response is created (201)
        $this->assertResponseStatusCodeSame(201);
        
        // Verify the JSON response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('Message dispatched successfully', $responseData['status']);
        $this->assertArrayHasKey('query', $responseData);
        $this->assertEquals($query, $responseData['query']);
        $this->assertArrayHasKey('time', $responseData);
        $this->assertArrayHasKey('jobTime', $responseData);
        
        // Get the transport from the container
        $transport = static::getContainer()->get('messenger.transport.async');
        
        // Ensure it's the in-memory transport (in test environment)
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        
        // Verify that a message was dispatched to the transport
        $this->assertCount(1, $transport->getSent());

        // Remove test from the database
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $jobRepository = $em->getRepository(Job::class);
        $jobToRemove = $jobRepository->findBy(['original_query' => $query]);
        if ($jobToRemove) {
            $removed = [];
            foreach ($jobToRemove as $job) {
                $removed[] = $job->getId();
                $em->remove($job);
            }
            $em->flush();

            // Job should be removed from the database
            foreach ($removed as $id) {
                $this->assertNull($em->find(Job::class, $id));
            }
        }
    }
    
    public function testDispatchEndpointWithExistingJob(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Create a job first
        $query = 'Where is TestCity located?';
        $em = static::getContainer()->get(EntityManagerInterface::class);
        
        $job = new Job();
        $job->setOriginalQuery($query);
        $job->setResult([
            'place_id' => 'test123',
            'description' => 'TestCity, TestCountry',
            'lat' => 12.345,
            'lng' => 67.890,
            'fresh' => true
        ]);
        
        $em->persist($job);
        $em->flush();

        $jobId = $job->getId();
        
        // Now make a request with the same query
        $client->request(
            'POST', 
            '/api/messages/dispatch', 
            ['query' => $query], 
            [], 
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
            ]
        );
        
        // Assert that the response is OK (200)
        $this->assertResponseStatusCodeSame(200);
        
        // Verify the JSON response structure for existing job
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('Message already processed', $responseData['status']);
        $this->assertArrayHasKey('result', $responseData);
        $this->assertArrayHasKey('place_id', $responseData['result']);
        
        // Get the transport
        $transport = static::getContainer()->get('messenger.transport.async');
        
        // No new message should be dispatched since we're reusing an existing job
        $this->assertCount(0, $transport->getSent());

        // Clean up: First find the entity again to ensure it's managed
        $jobToRemove = $em->find(Job::class, $jobId);
        if ($jobToRemove) {
            $em->remove($jobToRemove);
            $em->flush();
            
            // Job should be removed from the database
            $this->assertNull($em->find(Job::class, $jobId));
        }
    }
    
    public function testDispatchEndpointWithInvalidQuery(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Make a request with an invalid query (too short)
        $client->request(
            'POST', 
            '/api/messages/dispatch', 
            ['query' => 'a'], // Too short (less than 2 chars)
            [], 
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
            ]
        );
        
        // Assert that the response is Bad Request (400)
        $this->assertResponseStatusCodeSame(400);
        
        // Verify the error message
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Query parameter is missing or too short', $responseData['error']);
    }

    public function testDispatchExampleEndpoint(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        $content = 'Test message content ' . uniqid();
        
        // Make a request to the dispatch/example endpoint with JSON content
        $client->request(
            'POST', 
            '/api/messages/dispatch/example', 
            [], 
            [], 
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode(['content' => $content])
        );
        
        // Assert that the response is successful
        $this->assertResponseIsSuccessful();
        
        // Verify the JSON response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('Message dispatched successfully', $responseData['status']);
        $this->assertArrayHasKey('time', $responseData);
        
        // Get the transport from the container
        $transport = static::getContainer()->get('messenger.transport.async');
        
        // Ensure it's the in-memory transport (in test environment)
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        
        // Verify that a message was dispatched to the transport
        $this->assertCount(1, $transport->getSent());
        
        // Verify the message content
        $messages = $transport->getSent();
        $this->assertInstanceOf(\App\Message\ExampleMessage::class, $messages[0]->getMessage());
    }
}