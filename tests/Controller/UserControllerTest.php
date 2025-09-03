<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
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

    public function testGetUser(): void
    {
        //list 1 user and get 1 id
        $client = static::createClient();
        $token = $this->getJwtToken($client);

        $client->request(
            'GET',
            '/api/user',
            ['page' => 1, 'limit' => 1],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        self::assertResponseIsSuccessful();

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $randomUser = $responseData['users'][0] ?? null;

        $client->request(
            'GET', 
            sprintf('/api/user/%d', $randomUser['id']),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        self::assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('email', $responseData);
    }

    public function testCreateUser(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'testpassword'
        ];
        
        $client->request(
            'POST',
            '/api/user',
            $userData,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayHasKey('email', $responseData);

        // cleanup
        $userId = $responseData['id'];

        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->find($userId);

        $this->assertNotNull($user);
        
        $em->remove($user);
        $em->flush();
    }
    
    public function testListUsers(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        $client->request(
            'GET',
            '/api/user?page=1&limit=10',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        self::assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('page', $responseData);
        $this->assertArrayHasKey('limit', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('users', $responseData);
        $this->assertIsArray($responseData['users']);
    }
    
    public function testUserNotFound(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        $client->request(
            'GET',
            '/api/user/9999', // Non-existent user ID
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );
        
        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('User not found', $responseData['error']);
    }
}
