<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\ClientOutput;
use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ClientOutputDataTransformer implements DataTransformerInterface
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
    }
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new ClientOutput();
        $output->setId($data->getId());
        $output->setName($data->getName());
        $output->setUrl($data->getUrl());
        $output->setQuota($data->getQuota());
        $output->setDescription($data->getDescription());
        $output->setIsActive($data->getIsActive());
        //      $output->addPreScript($preScripts);
        //      $output->addPostScript($postScripts);
        $output->setMaxParallelJobs($data->getMaxParallelJobs());
        $output->setOwner($data->getOwner()->getId());
        $output->setSshArgs($data->getSshArgs());
        $output->setRsyncShortArgs($data->getRsyncShortArgs());
        $output->setRsyncLongArgs($data->getRsyncLongArgs());
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

