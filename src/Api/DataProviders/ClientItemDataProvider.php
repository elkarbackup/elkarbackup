<?php

namespace App\Api\DataProviders;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

final class ClientItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
    }
    
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Client::class === $resourceClass;
    }
    
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Client
    {
        $repository = $this->entityManager->getRepository('App:Client');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $id));
        return $query->getQuery()->getOneOrNullResult();
    }
}
