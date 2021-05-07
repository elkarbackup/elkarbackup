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
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
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
        $this->assertJsonContains(['name' => $clientName]);
    }

    public function testCreateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => -1,
                'name' => $clientName,
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
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $response = $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => "An exception occurred while executing 'INSERT INTO Client (description, isActive, name, url, quota, sshArgs, rsyncShortArgs, rsyncLongArgs, state, maxParallelJobs, data, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [null, 1, \"".$clientName."\", \"\", -1, null, null, null, \"NOT READY\", 1, null, 1]:\n\nSQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '".$clientName."' for key 'Client.UNIQ_C0E801635E237E06'"
        ]);
    }

    public function testCreateClientInvalidOwner(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
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

    public function testCreateClientUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive'        => true,
                'maxParallelJobs' => 1,
                'name'            => $clientName,
                'owner'           => 1,
                'postScripts'     => [self::UNEXISTING_ID],
                'quota'           => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Script "'.self::UNEXISTING_ID.'" does not exist',
        ]);
    }

    public function testCreateClientUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();;
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive'        => true,
                'maxParallelJobs' => 1,
                'name'            => $clientName,
                'owner'           => 1,
                'preScripts'     => [self::UNEXISTING_ID],
                'quota'           => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'Script "'.self::UNEXISTING_ID.'" does not exist',
        ]);
    }

    public function testDeleteClient(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => - 1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, [
            'name' => $clientName
        ]);
        $response = $httpClient->request('DELETE', $iri);
        $this->assertResponseIsSuccessful();
        $response = $httpClient->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteClientNotFound(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('DELETE', 'api/clients/'.self::UNEXISTING_ID);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'The client "'.self::UNEXISTING_ID.'" does not exist.',
        ]);
    }

    public function testGetClients(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
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

    public function testGetClientsUnauthenticated(): void
    {
        $response = static::createClient()->request('GET', '/api/clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetNonexistentClient(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => - 1
            ]
        ]);
        $response = $httpClient->request('GET', '/api/clients/'.self::UNEXISTING_ID);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'The client "'.self::UNEXISTING_ID.'" does not exist.',
        ]);
    }

    public function testGetClient(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => - 1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, [
            'name' => $clientName
        ]);
        $response = $httpClient->request('GET', $iri);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            'isActive' => true,
            'maxParallelJobs' => 1,
            'name' => $clientName,
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
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
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
        $iri = $this->findIriBy(Client::class, ['name' => $clientName]);
        $updatedName = $this->createClientName();
        $httpClient->request('PUT', $iri, [
            'json' => [
                'description' => 'description updated',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $updatedName,
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
        $this->assertJsonContains(['name' => $updatedName]);
        $this->assertJsonContains(['description' => 'description updated']);
    }

    public function testUpdateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1,
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => $clientName]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'description' => 'description updated',
                'isActive' => true,
                'maxParallelJobs' => -1,
                'name' => $clientName,
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
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $updateName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'description' => 'description',
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $updateName,
                'owner' => 1,
                'quota' => -1,
                'url' => 'root@172.17.0.1'
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => $clientName]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $updateName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => 'An error occurred',
        ]);
    }
    
    public function testUpdateClientNotFound(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('PUT', '/api/clients/'.self::UNEXISTING_ID, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testUpdateClientUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => $clientName]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'postScripts' => [self::UNEXISTING_ID],
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
            'hydra:description' => 'Script "'.self::UNEXISTING_ID.'" does not exist',
        ]);
    }
    
    public function testUpdateClientUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $httpClient->request('POST', '/api/clients', [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $iri = $this->findIriBy(Client::class, ['name' => $clientName]);
        
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => $clientName,
                'preScripts' => [self::UNEXISTING_ID],
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
            'hydra:description' => 'Script "'.self::UNEXISTING_ID.'" does not exist',
        ]);
    }
}
