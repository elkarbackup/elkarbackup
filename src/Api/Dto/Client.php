<?php
namespace App\Api\Dto;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource
 */
class Client
{
    
    protected $description;
    
    protected $id;
    
    protected $isActive = true;
    
    protected $jobs;
    
    protected $name;
    
    protected $url = '';
    
    protected $owner;

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

