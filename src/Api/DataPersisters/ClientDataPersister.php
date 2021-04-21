<?php
namespace App\Api\DataPersisters;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Client;
use App\Exception\APIException;
use App\Exception\PermissionException;
use App\Service\ClientService;
use \Exception;

class ClientDataPersister implements ContextAwareDataPersisterInterface
{
    private $clientService;
    
    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
    
    public function persist($data, array $context = [])
    {
        try {
            $this->clientService->save($data);
            return $data;
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
        
    }
    
    public function remove($data, array $context = [])
    {
        try{
            $this->clientService->delete($data->getId());
        } catch (PermissionException $e) {
            throw new PermissionException($e->getMessage());
        }catch (Exception $e) {
            throw new APIException($e->getMessage());
        }
        
    }
    
    public function supports($data, array $context = []): bool
    {
        return $data instanceof Client;
    }
}