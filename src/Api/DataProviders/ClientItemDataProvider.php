<?php

namespace App\Api\DataProviders;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\LoggerService;
use App\Service\RouterService;

final class ClientItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $entityManager;
    private $authChecker;
    private $logger;
    private $router;
    private $security;
    
    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, LoggerService $logger, RouterService $router, Security $security)
    {
        $this->entityManager = $em;
        $this->authChecker   = $authChecker;
        $this->logger        = $logger;
        $this->router        = $router;
        $this->security      = $security;
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
        $this->logger->debug(
            'View client %clientid%',
            array('%clientid%' => $id),
            array('link' => $this->router->generateClientRoute($id))
            );
        $this->entityManager->flush();
        return $query->getQuery()->getOneOrNullResult();
    }
}
