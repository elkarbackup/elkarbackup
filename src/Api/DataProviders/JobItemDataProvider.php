<?php
namespace App\Api\DataProviders;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Job;
use App\Exception\NotFoundException;
use App\Exception\PermissionException;
use App\Service\LoggerService;
use App\Service\RouterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use \Exception;

class JobItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
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

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Job
    {
        $repository = $this->entityManager->getRepository('App:Job');
        $query = $repository->createQueryBuilder('j');
        $query->where($query->expr()->eq('j.id', $id));
        if (null == $query->getQuery()->getOneOrNullResult()) {
            throw new NotFoundException(sprintf('The job "%s" does not exist.', $id));
        }
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            $query->join('j.client', 'c');
            $query->andWhere($query->expr()->eq('c.owner', $this->security->getToken()->getUser()->getId()));
            if (null == $query->getQuery()->getOneOrNullResult()) {
                throw new PermissionException(sprintf("Permission denied to get job %s", $id));
            }
        }
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

