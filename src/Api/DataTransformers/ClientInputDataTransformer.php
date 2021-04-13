<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\ClientInput;
use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ClientInputDataTransformer implements DataTransformerInterface
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
        if (isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            $client = $context[AbstractItemNormalizer::OBJECT_TO_POPULATE];
        } else {
            $client = new Client();
        }
        $client->setName($data->getName());
        $client->setUrl($data->getUrl());
        $client->setQuota($data->getQuota());
        $client->setDescription($data->getDescription());
        $client->setIsActive($data->getIsActive());
//      $client->addPreScript($preScripts);
//      $client->addPostScript($postScripts);
        $client->setMaxParallelJobs($data->getMaxParallelJobs());
        $client->setOwner($this->getOwner($data->getOwner()));
        $client->setSshArgs($data->getSshArgs());
        $client->setRsyncShortArgs($data->getRsyncShortArgs());
        $client->setRsyncLongArgs($data->getRsyncLongArgs());
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
    
    private function getOwner($id): ?User
    {
        $repository = $this->entityManager->getRepository('App:User');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $id));
        return $query->getQuery()->getOneOrNullResult();
    }
}

