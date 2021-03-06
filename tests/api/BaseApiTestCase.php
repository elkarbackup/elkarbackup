<?php
namespace App\Tests\api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use App\Entity\Script;

class BaseApiTestCase extends ApiTestCase
{
    protected function assertHydraError(string $description = null): void
    {
        if(isset($description)){
            $this->assertJsonContains([
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred',
                'hydra:description' => $description,
            ]);
        } else {
            $this->assertJsonContains([
                '@context' => '/api/contexts/Error',
                '@type' => 'hydra:Error',
                'hydra:title' => 'An error occurred'
            ]);
        }
    }

    protected function authenticate(): Client
    {
        return static::createClient([], [
            'auth_basic' => ['root', 'root'],
            'base_uri' => 'http://127.0.0.1'
        ]);
    }

    protected function getScriptId(Client $httpClient, string $scriptName): int
    {
        $iri = $this->findIriBy(Script::class, [
            'name' => $scriptName
        ]);
        $response = $httpClient->request('GET', $iri);
        
        return $response->toArray()['id'];
    }
    
    protected function postClient(Client $httpClient, RequestObject $client): RequestObject
    {
        $clientJson = $client->getData();
        $response = $httpClient->request('POST', '/api/clients', [
            'json' => $clientJson
        ]);
        if (201 == $response->getStatusCode()){
            $json = json_decode($response->getContent(), true);
            $client->setIri($json['@id']);
            $client->setId(explode('/', $json['@id'])[3]);
        }        
        return $client;
    }

    protected function postJob(Client $httpClient, RequestObject $job): RequestObject
    {
        $jobJson = $job->getData();
        $response = $httpClient->request('POST', '/api/jobs', [
            'json' => $jobJson
        ]);
        if (201 == $response->getStatusCode()){
            $json = json_decode($response->getContent(), true);
            $job->setIri($json['@id']);
            $job->setId(explode('/', $json['@id'])[3]);
        }
        return $job;
    }
}

