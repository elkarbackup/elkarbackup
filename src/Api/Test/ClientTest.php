<?php
namespace App\Api\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client as TestClient;
use App\Api\Test\BaseApiTestCase;
use App\Entity\Client;

class ClientTest extends BaseApiTestCase
{
    
    public function testCreateClient(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        //load fixtures for scripts
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp . '4',
                'owner' => 1,
                'postScripts' => [],
                'preScripts' => [],
                'quota' => - 1,
                'rsyncLongArgs' => '',
                'rsyncShortArgs' => '',
                'sshArgs' => '',
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://127.0.0.1/api/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'description' => 'ClientOutput/description',
                'id' => 'ClientOutput/id',
                'isActive' => 'ClientOutput/isActive',
                'maxParallelJobs' => 'ClientOutput/maxParallelJobs',
                'name' => 'ClientOutput/name',
                'owner' => 'ClientOutput/owner',
                'postScripts' => 'ClientOutput/postScripts',
                'preScripts' => 'ClientOutput/preScripts',
                'quota' => 'ClientOutput/quota',
                'rsyncLongArgs' => 'ClientOutput/rsyncLongArgs',
                'rsyncShortArgs' => 'ClientOutput/rsyncShortArgs',
                'sshArgs' => 'ClientOutput/sshArgs',
                'url' => 'ClientOutput/url'
            ],
            '@type' => 'Client'
        ]);
        $this->assertJsonContains(['name' => 'client' . $timestamp . '4']);
    }

    public function testCreateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => -1,
                'name' => 'client' . $timestamp,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'maxParallelJobs: Max parallel jobs value must be a positive integer',
        ]);
    }

    public function testCreateClientInvalidName(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $response = $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => "An exception occurred while executing 'INSERT INTO Client (description, isActive, name, url, quota, sshArgs, rsyncShortArgs, rsyncLongArgs, state, maxParallelJobs, data, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [null, 1, \"".'client'.$timestamp."\", \"\", -1, null, null, null, \"NOT READY\", 1, null, 1]:\n\nSQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '".'client'.$timestamp."' for key 'Client.UNIQ_C0E801635E237E06'"
        ]);
    }


    
    public function testCreateClientInvalidOwner(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp,
                'owner' => 2,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Incorrect owner id',
        ]);
    }

//     public function testCreateClientInvalidPostScript(): void
//     {
//         $httpClient = $this->authenticate();
//         $timestamp = $this->getTimestamp();
//         $httpClient->request('POST', '/api/clients', [
//             'json' => [
//                 'isActive'        => true,
//                 'maxParallelJobs' => 1,
//                 'name'            => 'client' . $timestamp,
//                 'owner'           => 1,
//                 'postScripts'     => [106],
//                 'quota'           => -1
//             ]
//         ]);
//         $this->assertResponseStatusCodeSame(400);
//         $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
//         $this->assertJsonContains([
//             '@context' => '/api/contexts/Error',
//             '@type' => 'hydra:Error',
//             'hydra:title' => 'An error occurred',
//             'hydra:description' => 'Script "106" is no a client post script',
//         ]);
//     }

//     public function testCreateClientInvalidPreScript(): void
//     {
//         $httpClient = $this->authenticate();
//         $timestamp = $this->getTimestamp();
//         $httpClient->request('POST', '/api/clients', [
//             'json' => [
//                 'isActive'        => true,
//                 'maxParallelJobs' => 1,
//                 'name'            => 'client' . $timestamp,
//                 'owner'           => 1,
//                 'postScripts'     => [101],
//                 'quota'           => -1
//             ]
//         ]);
//         $this->assertResponseStatusCodeSame(400);
//         $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
//         $this->assertJsonContains([
//             '@context' => '/api/contexts/Error',
//             '@type' => 'hydra:Error',
//             'hydra:title' => 'An error occurred',
//             'hydra:description' => 'Script "%s" is no a client pre script',
//         ]);
//     }

    public function testCreateClientUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive'        => true,
                'maxParallelJobs' => 1,
                'name'            => 'client' . $timestamp,
                'owner'           => 1,
                'postScripts'     => [1],
                'quota'           => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Script "1" does not exist',
        ]);
    }

    public function testCreateClientUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive'        => true,
                'maxParallelJobs' => 1,
                'name'            => 'client' . $timestamp,
                'owner'           => 1,
                'postScripts'     => [1],
                'quota'           => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Script "1" does not exist',
        ]);
    }

    public function testGetCollection(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp,
                'owner' => 1,
                'quota' => - 1
            ]
        ]);
        $response = $httpClient->request('GET', '/api/clients');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
        '@context' => '/api/contexts/Client',
        '@type' => 'hydra:Collection',
        '@id' => '/api/clients',
        ]);
    }

    public function testGetCollectionUnauthenticated(): void
    {
        $response = static::createClient()->request('GET', '/api/clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetInvalidItem(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp,
                'owner' => 1,
                'quota' => - 1
            ]
        ]);
        $response = $httpClient->request('GET', '/api/clients/' . $timestamp);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetItem(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client' . $timestamp,
                'owner' => 1,
                'quota' => - 1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, [
            'name' => 'client' . $timestamp
        ]);
        $response = $httpClient->request('GET', $iri);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'isActive' => true,
            'maxParallelJobs' => 1,
            'name' => 'client' . $timestamp,
            'owner' => 1,
            'postScripts' => [],
            'preScripts' => [],
            'quota' => '-1',
            'rsyncLongArgs' => null,
            'rsyncShortArgs' => null,
            'sshArgs' => null,
            'url' => ""
        ]);
    }

    public function testUpdateClient(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        //load fixtures for scripts
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client'.$timestamp.'0',
                'owner' => 1,
                'postScripts' => [],
                'preScripts' => [],
                'quota' => - 1,
                'rsyncLongArgs' => '',
                'rsyncShortArgs' => '',
                'sshArgs' => '',
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => 'client'.$timestamp.'0']);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'description' => 'description updated',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client',
                'owner' => 1,
                'quota' => - 1,
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => [
                '@vocab' => 'http://127.0.0.1/api/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'description' => 'ClientOutput/description',
                'id' => 'ClientOutput/id',
                'isActive' => 'ClientOutput/isActive',
                'maxParallelJobs' => 'ClientOutput/maxParallelJobs',
                'name' => 'ClientOutput/name',
                'owner' => 'ClientOutput/owner',
                'postScripts' => 'ClientOutput/postScripts',
                'preScripts' => 'ClientOutput/preScripts',
                'quota' => 'ClientOutput/quota',
                'rsyncLongArgs' => 'ClientOutput/rsyncLongArgs',
                'rsyncShortArgs' => 'ClientOutput/rsyncShortArgs',
                'sshArgs' => 'ClientOutput/sshArgs',
                'url' => 'ClientOutput/url'
            ],
            '@type' => 'Client'
        ]);
        $this->assertJsonContains(['name' => 'client']);
        $this->assertJsonContains(['description' => 'description updated']);
    }
    public function testUpdateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client'.$timestamp,
                'owner' => 1,
                'quota' => -1,
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => 'client'.$timestamp]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'description' => 'description updated',
                'isActive' => true,
                'maxParallelJobs' => -1,
                'name' => 'client'.$timestamp,
                'owner' => 1,
                'quota' => -1,
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'maxParallelJobs: Max parallel jobs value must be a positive integer',
        ]);
    }
    
    public function testUpdateClientInvalidName(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'clientToRepeat',
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'clientToUpdate',
                'owner' => 1,
                'quota' => -1,
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => 'clientToUpdate']);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'clientToRepeat',
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
//            'hydra:description' => "An exception occurred while executing 'UPDATE Client SET description = ?, name = ?, url = ?, quota = ? WHERE id = ?\' with params [null, 'clientToRepeat', '', -1, 8]::\n\nSQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'clientToRepeat' for key 'Client.UNIQ_C0E801635E237E06'"
        ]);
    }
    
    public function testUpdateClientUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client'.$timestamp,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => 'client'.$timestamp]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client'.$timestamp,
                'postScripts' => [0],
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Script "0" does not exist',
        ]);
    }
    
    public function testUpdateClientUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $timestamp = $this->getTimestamp();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client'.$timestamp,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => 'client'.$timestamp]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client'.$timestamp,
                'preScripts' => [0],
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Script "0" does not exist',
        ]);
    }
}

