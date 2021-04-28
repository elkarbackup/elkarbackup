<?php
namespace App\Api\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class JobByNameFilter
 * @package App\Api\Filter
 */
class JobByNameFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if ($property === 'name'){
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $queryBuilder->andWhere(sprintf('j.%s = :%s', $property, $parameterName));
            $queryBuilder->setParameter($parameterName, $value);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description['name']= [
            'property' => 'name',
            'type' => 'string',
            'required' => false,
            'openapi' => [
                'description' => 'Filter job by name',
            ],
        ];
        
        return $description;
    }
}

