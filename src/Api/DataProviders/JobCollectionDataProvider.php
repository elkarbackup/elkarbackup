<?php
namespace App\Api\DataProviders;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class JobCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $authChecker;
    private $collectionExtensions;
    private $entityManager;
    private $security;
    /**
     * Constructor
     */
    public function __construct(AuthorizationCheckerInterface $authChecker, EntityManagerInterface $em, iterable $collectionExtensions, Security $security)
    {
        $this->authChecker          = $authChecker;
        $this->collectionExtensions = $collectionExtensions;
        $this->entityManager        = $em;
        $this->security             = $security;
    }
    
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $repository = $this->entityManager->getRepository('App:Job');
        $query = $repository->createQueryBuilder('j')->addOrderBy('j.id', 'ASC');
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
            $query->join('j.client', 'c');
            $query->where($query->expr()->eq('c.owner', $this->security->getToken()->getUser()->getId()));
        }
        $queryNameGenerator = new QueryNameGenerator();
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($query, $queryNameGenerator, $resourceClass, $operationName);
            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass,$operationName)) {
                return $extension->getResult($query, $resourceClass, $operationName);
            }
        }
        
        return $query->getQuery()->getResult();
    }
    
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Job::class === $resourceClass;
    }
}