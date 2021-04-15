<?php
namespace App\Api\DataPersisters;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Job;
use App\Exception\APIException;
use App\Exception\PermissionException;
use App\Service\JobService;
use \Exception;

class JobDataPersister implements ContextAwareDataPersisterInterface
{
    private $jobService;
    
    public function __construct(JobService $clientService)
    {
        $this->jobService = $clientService;
    }
    
    public function persist($data, array $context = [])
    {
        try {
            $this->jobService->save($data);
            return $data;
        } catch (Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
        
    }
    
    public function supports($data, array $context = []): bool
    {
        return $data instanceof Job;
    }
    
    public function remove($data, array $context = [])
    {
        try{
            $this->jobService->delete($data->getId());
        } catch (PermissionException $e) {
            throw new PermissionException($e->getMessage());
        }catch (Exception $e) {
            throw new APIException($e->getMessage());
        }
        
    }
}

