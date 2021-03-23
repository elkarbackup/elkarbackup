<?php
namespace App\Api\DataProviders;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class ClientCollectionDataProvider implements ContextAwareCollectionDataProviderInterface
{
    private $entityManager;
    private $collectionExtensions;
    /**
     * Constructor
     */
    public function __construct(EntityManagerInterface $em, iterable $collectionExtensions)
    {
        $this->entityManager        = $em;
        $this->collectionExtensions = $collectionExtensions;
    }
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $repository = $this->entityManager->getRepository('App:Client');
        $query = $repository->createQueryBuilder('c')->addOrderBy('c.id', 'ASC');
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

