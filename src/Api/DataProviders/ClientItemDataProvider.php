<?php

namespace App\Api\DataProviders;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

final class ClientItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $entityManager;
    private $authChecker;
    private $security;
    
    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, Security $security)
    {
        $this->entityManager        = $em;
        $this->authChecker          = $authChecker;
        $this->security             = $security;
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
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            $query->where($query->expr()->eq('c.id', $id))->andWhere($query->expr()->eq('c.owner', $this->security->getToken()->getUser()->getId()));
        } else {
            $query->where($query->expr()->eq('c.id', $id));
        }
        return $query->getQuery()->getOneOrNullResult();
    }
}
