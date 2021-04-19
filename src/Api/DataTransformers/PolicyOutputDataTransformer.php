<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\PolicyOutput;
use App\Entity\Policy;
use Doctrine\ORM\EntityManagerInterface;

class PolicyOutputDataTransformer implements DataTransformerInterface
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
    }
    
    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        $output = new PolicyOutput();
        $output->setId($data->getId());
        $output->setName($data->getName());
        $output->setDescription($data->getDescription());
        return $output;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return PolicyOutput::class === $to && $data instanceof Policy;
    }
}

