<?php

namespace App\Api\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SearchFilter
 * @package App\Filter
 */
class SearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if ($property === 'client'){
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
        $description['client']= [
                'property' => 'client',
                'type' => 'integer',
                'required' => false,
                'openapi' => [
                    'description' => 'Jobs of client',
                ],
        ];
        
        return $description;
    }
    
}