<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\ClientOutput;
use App\Entity\Client;

class ClientOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new ClientOutput();
        $output->id = $data->getId();
        $output->name = $data->getName();
        return $output;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ClientOutput::class === $to && $data instanceof Client;
    }
}

