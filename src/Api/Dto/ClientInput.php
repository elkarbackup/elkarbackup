<?php
namespace App\Api\Dto;

class ClientInput
{
    protected $id;
    protected $name;
    protected $url;
    protected $quota = -1;
    protected $maxParallelJobs = 1;
    protected $owner;
    /**
     * @return integer
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param integer $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }

    /**
     * @return integer
     */
    public function getMaxParallelJobs()
    {
        return $this->maxParallelJobs;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param integer $quota
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;
    }

    /**
     * @param integer $maxParallelJobs
     */
    public function setMaxParallelJobs($maxParallelJobs)
    {
        $this->maxParallelJobs = $maxParallelJobs;
    }

}

