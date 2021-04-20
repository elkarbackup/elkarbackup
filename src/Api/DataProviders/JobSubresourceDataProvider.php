<?php
namespace App\Api\DataProviders;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Job;

class JobSubresourceDataProvider implements SubresourceDataProviderInterface
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
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Job::class === $resourceClass;
    }
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $repository = $this->entityManager->getRepository('App:Job');
        $query = $repository->createQueryBuilder('c')->addOrderBy('c.id', 'ASC');
        $queryNameGenerator = new QueryNameGenerator();
        $client = $context['subresource_resources'];
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($query, $queryNameGenerator, $resourceClass, $operationName);
            if ($extension instanceof QueryResultCollectionExtensionInterface && $extension->supportsResult($resourceClass,$operationName)) {
                return $extension->getResult($query, $resourceClass, $operationName);
            }
        }
        
        return $query->getQuery()->getResult();
    }
}

