<?php
namespace App\Api\DataTransformers;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Api\Dto\UserOutput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserOutputDataTransformer implements DataTransformerInterface
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
        $output = new UserOutput();
        $output->setId($data->getId());
        $output->setUsername($data->getUsername());
        $output->setEmail($data->getEmail());
        $output->setIsActive($data->getIsActive());
        $output->setRoles($data->getRoles());
        return $output;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return UserOutput::class === $to && $data instanceof User;
    }
}

