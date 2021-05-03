<?php
namespace App\Api\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client as TestClient;
use App\Api\Test\BaseApiTestCase;
use App\Entity\Client;

class ClientTest extends BaseApiTestCase
{
    
    public function testGetCollection(): void
    {
        $this->createUser();
        $httpClient = static::createClient([], [
            'auth_basic' => ['root', 'root'],
            'base_uri' => 'http://127.0.0.1'
        ]);
        $response = $httpClient->request('GET', '/api/clients');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertMatchesResourceCollectionJsonSchema(Client::class);
    }
    public function testGetCollectionUnauthenticated(): void
    {
        $response = static::createClient()->request('GET', '/api/clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetInvalidItem(): void
    {
        
    }

    public function testGetItem(): void
    {
        
    }

    public function testCreateClient(): void
    {
        
    }

    public function testCreateClientInvalidName(): void
    {
        
    }

    public function testCreateClientInvalidMaxParallelJobs(): void
    {
        
    }
}

