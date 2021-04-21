<?php
namespace App\Api\DataProviders;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Job;
use App\Service\LoggerService;
use App\Service\RouterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class JobItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $authChecker;
    private $entityManager;
    private $logger;
    private $router;

    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, LoggerService $logger, RouterService $router)
    {
        $this->authChecker   = $authChecker;
        $this->entityManager = $em;
        $this->logger        = $logger;
        $this->router        = $router;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Job
    {
        $repository = $this->entityManager->getRepository('App:Job');
        $query = $repository->createQueryBuilder('c');
        $query->where($query->expr()->eq('c.id', $id));
        $idClient = $query->getQuery()->getOneOrNullResult()->getClient()->getId();
        $this->logger->debug(
            'View job %clientid%',
            array('%clientid%' => $id),
            array('link' => $this->router->generateJobRoute($id, $idClient))
            );
        $this->entityManager->flush();
        return $query->getQuery()->getOneOrNullResult();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Job::class === $resourceClass;
    }
}

