<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\ClientInput;
use App\Entity\Client;

class ClientInputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $client = new Client();
        $client->setName($data->getName());
        $client->setUrl($data->getUrl());
        $client->setQuota($data->getQuota());
        $client->setMaxParallelJobs($data->getMaxParallelJobs());
        return $client;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        // in the case of an input, the value given here is an array (the JSON decoded).
        // if it's a book we transformed the data already
        if ($data instanceof Client) {
            return false;
        }
        
        return Client::class === $to && null !== ($context['input']['class'] ?? null);
    }
}

