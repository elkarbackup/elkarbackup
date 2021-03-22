<?php
namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource
 */
class Job
{
    protected $client;
    
    protected $id;
    
    protected $name;
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
}

