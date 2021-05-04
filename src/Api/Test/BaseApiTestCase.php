<?php
namespace App\Api\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;

class BaseApiTestCase extends ApiTestCase
{
    protected function authenticate(): Client
    {
        return static::createClient([], [
            'auth_basic' => ['root', 'root'],
            'base_uri' => 'http://127.0.0.1'
        ]);
    }
}

