<?php
namespace App\Api\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client as TestClient;
use App\Api\Test\BaseApiTestCase;
use App\Entity\Client;

class ClientTest extends BaseApiTestCase
{
    
    public function testGetCollection(): void
    {
        $httpClient = $this->authenticate();
        $httpClient->request('POST', '/api/clients', ['json' => [
            'isActive'        => true,
            'maxParallelJobs' => 1,
            'name'            => 'client'.$this->getTimestamp(),
            'owner'           => 1,
            'quota'           => -1
        ]]);
        $response = $httpClient->request('GET', '/api/clients');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }
    public function testGetCollectionUnauthenticated(): void
    {
        $response = static::createClient()->request('GET', '/api/clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetInvalidItem(): void
    {
        $httpClient = $this->authenticate();
        $timestamp=$this->getTimestamp();
        $httpClient->request('POST', '/api/clients', ['json' => [
            'isActive'        => true,
            'maxParallelJobs' => 1,
            'name'            => 'client'.$timestamp,
            'owner'           => 1,
            'quota'           => -1
        ]]);
        $response = $httpClient->request('GET', '/api/clients/'.$this->getTimestamp());
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetItem(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', ['json' => [
            'isActive'        => true,
            'maxParallelJobs' => 1,
            'name'            => 'client'.$timestamp,
            'owner'           => 1,
            'quota'           => -1
        ]]);
        $iri = $this->findIriBy(Client::class, ['name' => 'client'.$this->getTimestamp()]);
        $response = $httpClient->request('GET', $iri);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

//     public function testCreateClient(): void
//     {
        
//     }

//     public function testCreateClientInvalidName(): void
//     {
        
//     }

//     public function testCreateClientInvalidMaxParallelJobs(): void
//     {
        
//     }
}

