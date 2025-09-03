<?php

namespace App\Tests\Controller;

use App\Entity\Job;
use App\Service\DateHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GeocodingControllerTest extends WebTestCase
{
    
    public function testPostRequestWithValidData(): void
    {
        $client = static::createClient();
        
        // Create test data
        $testData = [
            'location' => 'Bucharest',
            'query' => 'Where is Bucharest?',
            'original_message' => 'Tell me about Bucharest',
            'messageId' => 'test-' . uniqid(),
            'sentAt' => (new \DateTimeImmutable())->getTimestamp() * 1000 // Convert to milliseconds
        ];
        
        // Make POST request
        $client->request(
            'POST', 
            '/geocoding', 
            $testData, 
            [], 
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']
        );
        
        self::assertResponseIsSuccessful();
        
        // Verify response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('location', $responseData);
        $this->assertArrayHasKey('result', $responseData);
        $this->assertEquals('Bucharest', $responseData['location']);
        
        // Verify result contains expected fields
        $this->assertArrayHasKey('place_id', $responseData['result']);
        $this->assertArrayHasKey('description', $responseData['result']);
        $this->assertArrayHasKey('lat', $responseData['result']);
        $this->assertArrayHasKey('lng', $responseData['result']);
        
        // Verify a job was created in the database
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $job = $em->getRepository(Job::class)->findOneBy(['original_query' => $testData['original_message']]);
        
        $this->assertNotNull($job, 'Job was not created in the database');
        $this->assertEquals($testData['location'], $job->getLocation());
        $this->assertEquals($testData['query'], $job->getQuery());
        $this->assertEquals($testData['messageId'], $job->getMessageId());

        $jobId = $job->getId();
        
        // Clean the job from the DB and test
        $em->remove($job);
        $em->flush();

        // Job should be removed from the database
        $this->assertNull($em->find(Job::class, $jobId));
    }
    
    public function testPostRequestWithInvalidLocation(): void
    {
        $client = static::createClient();
        
        // Test with too short location
        $client->request(
            'POST', 
            '/geocoding', 
            ['location' => 'a'], // Too short location
            [], 
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']
        );
        
        // Verify bad request response
        self::assertResponseStatusCodeSame(400);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Location is too short', $responseData['error']);
    }
    
    public function testPostRequestWithMissingFields(): void
    {
        $client = static::createClient();
        
        // Test with valid location but missing other fields
        $client->request(
            'POST', 
            '/geocoding', 
            ['location' => 'Paris'], // Missing other fields
            [], 
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']
        );
        
        // Should still work without the other fields
        self::assertResponseIsSuccessful();
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Paris', $responseData['location']);
        
        // No job should be created without original_message
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $jobs = $em->getRepository(Job::class)->findBy(['location' => 'Paris']);
        
        // Since original_message is required, no job should be created
        $this->assertEmpty($jobs, 'A job was incorrectly created without original_message');
    }
}