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
    
    protected function postClient(Client $httpClient, array $clientJson): void
    {
        $httpClient->request('POST', '/api/clients', [
            'json' => $clientJson
        ]);
    }
    protected function createClientName(): string
    {
        $time = new \DateTime();
        $clientName = 'client_'.$time->getTimestamp().'_'.rand(1000, 9999);
        return $clientName;
    }
}

