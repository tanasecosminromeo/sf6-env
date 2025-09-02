<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private function getJwtToken($client, $username = 'user', $password = 'password'): string
    {
        $client->request('POST', '/api/login_check', [
            'username' => $username,
            'password' => $password,
        ]);
        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['token'] ?? '';
    }

    public function testIndex(): void
    {
        $client = static::createClient();
        $token = $this->getJwtToken($client);
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
        $client->request('GET', '/api/user/1');

        self::assertResponseIsSuccessful();
    }
}
