<?php

namespace App\Api\DataProviders;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Client;
use App\Service\LoggerService;
use App\Service\RouterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use App\Exception\NotFoundException;
use App\Exception\PermissionException;

final class ClientItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $authChecker;
    private $entityManager;
    private $logger;
    private $router;
    private $security;
    
    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, LoggerService $logger, RouterService $router, Security $security)
    {
        $this->authChecker   = $authChecker;
        $this->entityManager = $em;
        $this->logger        = $logger;
        $this->router        = $router;
        $this->security      = $security;
    }
    
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Client
    {
        $repository = $this->entityManager->getRepository('App:Client');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $id));
        if (null == $query->getQuery()->getOneOrNullResult()) {
            throw new NotFoundException(sprintf('The client "%s" does not exist.', $id));
        }
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            $query->andWhere($query->expr()->eq('c.id', $id))->andWhere($query->expr()->eq('c.owner', $this->security->getToken()->getUser()->getId()));
            if (null == $query->getQuery()->getOneOrNullResult()) {
                throw new PermissionException(sprintf("Permission denied to get client %s", $id));
            }
        }
        $this->logger->debug(
            'View client %clientid%',
            array('%clientid%' => $id),
            array('link' => $this->router->generateClientRoute($id))
            );
        $this->entityManager->flush();
        return $query->getQuery()->getOneOrNullResult();
    }
    
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Client::class === $resourceClass;
    }
}
