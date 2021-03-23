<?php
namespace App\Api\DataPersisters;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class ClientDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager        = $em;
    }
    
    public function persist($data, array $context = [])
    {
        $this->entityManager->persist($data);
        $this->entityManager->flush();
        return $data;
    }
    
    public function supports($data, array $context = []): bool
    {
        return $data instanceof Client;
    }
    
    public function remove($data, array $context = [])
    {
        // call your persistence layer to delete $data
    }
}