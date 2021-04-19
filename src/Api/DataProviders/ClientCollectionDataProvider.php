<?php
namespace App\Api\DataProviders;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class ClientCollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $entityManager;
    private $authChecker;
    private $collectionExtensions;
    private $security;
    /**
     * Constructor
     */
    public function __construct(EntityManagerInterface $em, AuthorizationCheckerInterface $authChecker, Security $security, iterable $collectionExtensions)
    {
        $this->entityManager        = $em;
        $this->authChecker          = $authChecker;
        $this->collectionExtensions = $collectionExtensions;
        $this->security             = $security;
    }
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Client::class === $resourceClass;
    }
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $repository = $this->entityManager->getRepository('App:Client');
        $query = $repository->createQueryBuilder('c')->addOrderBy('c.id', 'ASC');
        if (!$this->authChecker->isGranted('ROLE_ADMIN')) {
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
}

