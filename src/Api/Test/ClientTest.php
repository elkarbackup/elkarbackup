<?php
namespace App\Api\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client as TestClient;
use App\Api\Test\BaseApiTestCase;
use App\Entity\Client;
use App\Entity\Script;

class ClientTest extends BaseApiTestCase
{
    public function testCreateClient(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $clientJson = ClientMother::named($clientName);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($clientJson);
        $this->assertHydraContext();
    }

    public function testCreateClientAllParameters(): void 
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $scriptId = $this->getScriptId($httpClient, 'script_all_true');
        $clientJson = ClientMother::withAllParameters(
            $clientName, 
            1, 
            "some description", 
            false, 
            2, 
            [$scriptId], 
            [$scriptId],
            -1, 
            "rsync long arguments", 
            "rsync short arguments", 
            "ssh arguments", 
            "root@172.0.0.1"
        );
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($clientJson);
        $this->assertHydraContext();
    }
    public function testCreateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $clientJson = ClientMother::withMaxParallelJobs($clientName, -1);
        $this->postClient($httpClient, $clientJson);
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
        $clientJson = ClientMother::named($clientName);
        $this->postClient($httpClient, $clientJson);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertHydraError("An exception occurred while executing 'INSERT INTO Client (description, isActive, name, url, quota, sshArgs, rsyncShortArgs, rsyncLongArgs, state, maxParallelJobs, data, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [null, 1, \"".$clientName."\", \"\", -1, null, null, null, \"NOT READY\", 1, null, 1]:\n\nSQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '".$clientName."' for key 'Client.UNIQ_C0E801635E237E06'");
    }

    public function testCreateClientInvalidOwner(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $clientJson = ClientMother::withOwner($clientName, self::UNEXISTING_ID);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect owner id');
    }

    public function testCreateClientInvalidPostScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_client_post');
        $clientName = $this->createClientName();
        $clientJson = ClientMother::withPostScripts($clientName, [$scriptId]);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a client post script');
    }

    public function testCreateClientInvalidPreScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_client_pre');
        $clientName = $this->createClientName();
        $clientJson = ClientMother::withPreScripts($clientName, [$scriptId]);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a client pre script');
    }
    public function testCreateClientUnexistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $clientJson = ClientMother::withPostScripts($clientName, [self::UNEXISTING_ID]);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }

    public function testCreateClientUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $clientJson = ClientMother::withPreScripts($clientName, [self::UNEXISTING_ID]);
        $this->postClient($httpClient, $clientJson);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }

    public function testDeleteClient(): void
    {
        $httpClient = $this->authenticate();
        $iri = $this->findIriBy(Client::class, [
            'name' => 'client_to_delete'
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
        $this->assertHydraError('The client "'.self::UNEXISTING_ID.'" does not exist.');
    }

    public function testGetClients(): void
    {
        $httpClient = $this->authenticate();
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
        $response = $httpClient->request('GET', '/api/clients/'.self::UNEXISTING_ID);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The client "'.self::UNEXISTING_ID.'" does not exist.');
    }

    public function testGetClient(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $clientJson = ClientMother::named($clientName);
        $this->postClient($httpClient, $clientJson);
        $iri = $this->findIriBy(Client::class, [
            'name' => $clientName
        ]);
        $response = $httpClient->request('GET', $iri);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($clientJson);
        $this->assertHydraContext();
    }

    public function testUpdateClient(): void
    {
        $httpClient = $this->authenticate();
        $iri = $this->findIriBy(Client::class, ['name' => 'client_2']);
        $scriptId = $this->getScriptId($httpClient, 'script_all_true');
        $updatedName = $this->createClientName();
        $updateClientJson = ClientMother::withAllParameters(
            $updatedName,
            1,
            "description updated",
            true,
            5,
            [$scriptId],
            [],
            -1,
            "rsync long arguments updated",
            "rsync short arguments updated",
            "ssh arguments updated",
            "root@172.0.0.2"
        );
        $httpClient->request('PUT', $iri, [
            'json' => $updateClientJson
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($updateClientJson);
        $this->assertHydraContext();
    }

    public function testUpdateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $iri = $this->findIriBy(Client::class, ['name' => 'client_3']);
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
        $iri = $this->findIriBy(Client::class, ['name' => 'client_4']);
        $httpClient->request('PUT', $iri, [
            'json' => [
                'isActive' => true,
                'maxParallelJobs' => 1,
                'name' => 'client_8',
                'owner' => 1,
                'quota' => -1
            ]
        ]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertHydraError();
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
        $iri = $this->findIriBy(Client::class, ['name' => 'client_5']);
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
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }
    
    public function testUpdateClientUnexistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $clientName = $this->createClientName();
        $iri = $this->findIriBy(Client::class, ['name' => 'client_6']);
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
        $this->assertHydraError('Script "'.self::UNEXISTING_ID.'" does not exist');
    }
}
