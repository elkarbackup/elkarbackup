<?php
namespace App\Tests\api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client as TestClient;
use App\Entity\Client;
use App\Entity\Script;
use App\Tests\api\BaseApiTestCase;

class ClientTest extends BaseApiTestCase
{
    public function testCreateClient(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($client->getData());
        $this->assertJsonContains($client->getContext());
    }

    public function testCreateClientAllParameters(): void 
    {
        $httpClient = $this->authenticate();
        $preScript1 = $this->getScriptId($httpClient, 'script_client_pre_1');
        $postScript1 = $this->getScriptId($httpClient, 'script_client_post_1');
        $preScript2 = $this->getScriptId($httpClient, 'script_client_pre_2');
        $postScript2 = $this->getScriptId($httpClient, 'script_client_post_2');
        $client = ClientMother::withAllParameters(
            1, 
            "some description", 
            false, 
            2, 
            [$postScript1, $postScript2], 
            [$preScript1, $preScript2],
            -1, 
            "rsync long arguments", 
            "rsync short arguments", 
            "ssh arguments", 
            "root@172.0.0.1"
        );
        $client = $this->postClient($httpClient, $client);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($client->getData());
        $this->assertJsonContains($client->getContext());
    }
    public function testCreateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::withInvalidMaxParallelJobs();
        $client = $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'maxParallelJobs: Max parallel jobs value must be a positive integer',
        ]);
    }

    public function testCreateClientRepeatedName(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $this->postClient($httpClient, $client);
        $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(400);
        #Unsuitable error management issued in #554. Error message will change when issue is resolved
        $this->assertHydraError("An exception occurred while executing 'INSERT INTO Client (description, isActive, name, url, quota, sshArgs, rsyncShortArgs, rsyncLongArgs, state, maxParallelJobs, data, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)' with params [null, 1, \"".$client->getName()."\", \"\", -1, null, null, null, \"NOT READY\", 1, null, 1]:\n\nSQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '".$client->getName()."' for key 'Client.UNIQ_C0E801635E237E06'");
    }

    public function testCreateClientNonExistentOwner(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::withNonExistentOwner();
        $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Incorrect owner id');
    }

    public function testCreateClientInvalidPostScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_client_post');
        $client = ClientMother::withPostScripts([$scriptId]);
        $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a client post script');
    }

    public function testCreateClientInvalidPreScript(): void
    {
        $httpClient = $this->authenticate();
        $scriptId = $this->getScriptId($httpClient, 'script_not_client_pre');
        $client = ClientMother::withPreScripts([$scriptId]);
        $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$scriptId.'" is not a client pre script');
    }

    public function testCreateClientNonExistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::withNonExistentPostScripts();
        $clientJson = $client->getData();
        $script = $clientJson['postScripts'][0];
        $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$script.'" does not exist');
    }

    public function testCreateClientNonExistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::withNonExistentPreScripts();
        $clientJson = $client->getData();
        $script = $clientJson['preScripts'][0];
        $this->postClient($httpClient, $client);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$script.'" does not exist');
    }

    public function testDeleteClient(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $this->postClient($httpClient, $client);
        $iri = $client->getIri();
        $response = $httpClient->request('DELETE', $iri);
        $this->assertResponseIsSuccessful();
        $response = $httpClient->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteClientNotFound(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('DELETE', ClientMother::getNonExistentIri());
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The client "'.ClientMother::UNEXISTING_ID.'" does not exist.');
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

    public function testGetNonExistentClient(): void
    {
        $httpClient = $this->authenticate();
        $response = $httpClient->request('GET', ClientMother::getNonExistentIri());
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('The client "'.ClientMother::UNEXISTING_ID.'" does not exist.');
    }

    public function testGetClient(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $iri = $this->findIriBy(Client::class, [
            'name' => $client->getName()
        ]);
        $response = $httpClient->request('GET', $iri);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($client->getData());
        $this->assertJsonContains($client->getContext());
    }

    public function testUpdateClient(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $postScript1 = $this->getScriptId($httpClient, 'script_client_post_1');
        $preScript1 = $this->getScriptId($httpClient, 'script_client_pre_1');
        $postScript2 = $this->getScriptId($httpClient, 'script_client_post_2');
        $preScript2 = $this->getScriptId($httpClient, 'script_client_pre_2');
        $updateClient = ClientMother::withAllParameters(
            1,
            "description updated",
            true,
            5,
            [$postScript1, $postScript2],
            [$preScript1, $preScript2],
            -1,
            "rsync long arguments updated",
            "rsync short arguments updated",
            "ssh arguments updated",
            "root@172.0.0.2"
        );
        $updateClientJson = $updateClient->getData();
        $httpClient->request('PUT', $client->getIri(), [
            'json' => $updateClientJson
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains($updateClientJson);
        $this->assertJsonContains($updateClient->getContext());
    }

    public function testUpdateClientInvalidMaxParallelJobs(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $updateClient = ClientMother::withInvalidMaxParallelJobs();
        $httpClient->request('PUT', $client->getIri(), ['json' => $updateClient->getData()]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'maxParallelJobs: Max parallel jobs value must be a positive integer',
        ]);
    }

    public function testUpdateClientRepeatedName(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $updateClient = ClientMother::named('client_8');
        $httpClient->request('PUT', $client->getIri(), ['json' => $updateClient->getData()]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertHydraError();
    }

    public function testUpdateClientNotFound(): void
    {
        $httpClient = $this->authenticate();
        $httpClient->request('PUT', ClientMother::getNonExistentIri(), [
            'json' => ClientMother::base()
        ]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testUpdateClientNonExistentPostScript(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $updateClient = ClientMother::withNonExistentPostScripts();
        $updateClientJson = $updateClient->getData();
        $script = $updateClientJson['postScripts'][0];
        $httpClient->request('PUT', $client->getIri(), ['json' => $updateClientJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$script.'" does not exist');
    }

    public function testUpdateClientNonExistentPreScript(): void
    {
        $httpClient = $this->authenticate();
        $client = ClientMother::base();
        $client = $this->postClient($httpClient, $client);
        $updateClient = ClientMother::withNonExistentPreScripts();
        $updateClientJson = $updateClient->getData();
        $script = $updateClientJson['preScripts'][0];
        $httpClient->request('PUT', $client->ge, ['json' =>$updateClientJson]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertHydraError('Script "'.$script.'" does not exist');
    }
}
