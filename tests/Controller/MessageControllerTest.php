<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

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
    
    public function testDispatchEndpoint(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Make a request to the dispatch endpoint
        $client->request('POST', '/api/messages/dispatch', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        
        // Assert that the response is successful
        $this->assertResponseIsSuccessful();
        
        // Verify the JSON response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('Message dispatched successfully', $responseData['status']);
        
        // Get the transport from the container
        $transport = static::getContainer()->get('messenger.transport.async');
        
        // Ensure it's the in-memory transport (in test environment)
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        
        // Verify that a message was dispatched to the transport
        $this->assertCount(1, $transport->getSent());
    }


    public function testDispatchExampleEndpoint(): void
    {
                $client = static::createClient();
        $token = $this->getJwtToken($client);
        
        // Make a request to the dispatch endpoint
        $client->request('POST', '/api/messages/dispatch/example', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        
        // Assert that the response is successful
        $this->assertResponseIsSuccessful();
        
        // Verify the JSON response structure
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('Message dispatched successfully', $responseData['status']);
        
        // Get the transport from the container
        $transport = static::getContainer()->get('messenger.transport.async');
        
        // Ensure it's the in-memory transport (in test environment)
        $this->assertInstanceOf(InMemoryTransport::class, $transport);
        
        // Verify that a message was dispatched to the transport
        $this->assertCount(1, $transport->getSent());
    }
}
