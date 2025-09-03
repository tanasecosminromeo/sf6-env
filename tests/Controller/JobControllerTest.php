<?php

namespace App\Tests\Controller;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JobControllerTest extends WebTestCase
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
    
    public function testGetJob(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // First create a test job to retrieve
        $em = static::getContainer()->get(EntityManagerInterface::class);
        
        $job = new Job();
        $job->setOriginalQuery('Test job for get endpoint');
        $job->setLocation('London');
        $job->setQuery('London coordinates');
        $job->setResult([
            'place_id' => 'test123',
            'description' => 'London, UK',
            'lat' => 51.5074,
            'lng' => 0.1278,
            'fresh' => true
        ]);
        
        $em->persist($job);
        $em->flush();
        
        $jobId = $job->getId();
        
        // Make the API request
        $client->request(
            'GET',
            '/api/jobs/' . $jobId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        // Assert successful response
        $this->assertResponseIsSuccessful();
        
        // Verify response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('original_query', $responseData);
        $this->assertArrayHasKey('location', $responseData);
        $this->assertArrayHasKey('result', $responseData);
        
        // Verify data matches
        $this->assertEquals($jobId, $responseData['id']);
        $this->assertEquals('Test job for get endpoint', $responseData['original_query']);
        $this->assertEquals('London', $responseData['location']);
        $this->assertEquals('London coordinates', $responseData['query']);
        
        $jobId = $job->getId();
        // Clean up
        $jobToRemove = $em->find(Job::class, $jobId);
        if ($jobToRemove) {
            $em->remove($jobToRemove);
            $em->flush();
        }
    }
    
    public function testGetJobNotFound(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);

        $jobRepository = static::getContainer()->get(EntityManagerInterface::class)->getRepository(Job::class);
        $maxJobId = $jobRepository->createQueryBuilder('j')
            ->select('MAX(j.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Request non-existent job
        $client->request(
            'GET',
            '/api/jobs/' . ($maxJobId + 1), // Using an ID that doesn't exist
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        // Assert 404 response
        $this->assertResponseStatusCodeSame(404);
        
        // Verify error message
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Job not found', $responseData['error']);
    }
    
    public function testSearchJob(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Create test jobs for search
        $em = static::getContainer()->get(EntityManagerInterface::class);
        
        $uniqueId = uniqid('search');
        $testJobs = [];
        
        // Create 3 jobs with similar queries
        for ($i = 1; $i <= 3; $i++) {
            $job = new Job();
            $job->setOriginalQuery("Test job $uniqueId with search term number $i");
            $job->setLocation("City $i");
            $job->setResult(['test' => true]);
            
            $em->persist($job);
            $testJobs[] = $job;
        }
        
        $em->flush();
        
        // Search for jobs
        $client->request(
            'GET',
            '/api/jobs/search?query=' . urlencode($uniqueId),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        // Assert successful response
        $this->assertResponseIsSuccessful();
        
        // Verify response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('query', $responseData);
        $this->assertArrayHasKey('jobs', $responseData);
        
        // Should find all 3 jobs
        $this->assertEquals(3, $responseData['total']);
        $this->assertCount(3, $responseData['jobs']);
        
        // Clean up
        foreach ($testJobs as $job) {
            $jobId = $job->getId();
            // Clean up
            $jobToRemove = $em->find(Job::class, $jobId);
            if ($jobToRemove) {
                $em->remove($jobToRemove);
            }
        }
        $em->flush();
    }
    
    public function testSearchJobMissingQuery(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Search without query parameter
        $client->request(
            'GET',
            '/api/jobs/search', // No query parameter
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        // Assert 400 response
        $this->assertResponseStatusCodeSame(400);
        
        // Verify error message
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Missing query parameter', $responseData['error']);
    }
    
    public function testListJobs(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Create test jobs for listing
        $em = static::getContainer()->get(EntityManagerInterface::class);
        
        $uniqueId = uniqid('list');
        $testJobs = [];
        
        // Create 5 jobs
        for ($i = 1; $i <= 5; $i++) {
            $job = new Job();
            $job->setOriginalQuery("Test job $uniqueId for listing $i");
            $job->setLocation("City $i");
            $job->setResult(['test' => true]);
            
            $em->persist($job);
            $testJobs[] = $job;
        }
        
        $em->flush();
        
        // List jobs with pagination
        $client->request(
            'GET',
            '/api/jobs?page=1&limit=3', // First page with 3 jobs per page
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        // Assert successful response
        $this->assertResponseIsSuccessful();
        
        // Verify response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('page', $responseData);
        $this->assertArrayHasKey('limit', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('jobs', $responseData);
        
        // Verify pagination
        $this->assertEquals(1, $responseData['page']);
        $this->assertEquals(3, $responseData['limit']);
        $this->assertGreaterThanOrEqual(5, $responseData['total']); // At least the 5 we added
        $this->assertLessThanOrEqual(3, count($responseData['jobs'])); // Should only return 3 per page
        
        // Test sorting
        $client->request(
            'GET',
            '/api/jobs?sort=createdAt&order=ASC',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        $this->assertResponseIsSuccessful();
        
        // Clean up
        foreach ($testJobs as $job) {
            $jobId = $job->getId();
            // Clean up
            $jobToRemove = $em->find(Job::class, $jobId);
            if ($jobToRemove) {
                $em->remove($jobToRemove);
            }
        }
        $em->flush();
    }
    
    public function testUnauthorizedAccess(): void
    {
        $client = static::createClient();
        
        // Try to access without authentication
        $client->request('GET', '/api/jobs');
        
        // Should return 401 Unauthorized
        $this->assertResponseStatusCodeSame(401);
        
        $client->request('GET', '/api/jobs/1');
        $this->assertResponseStatusCodeSame(401);
        
        $client->request('GET', '/api/jobs/search?query=test');
        $this->assertResponseStatusCodeSame(401);
    }
}