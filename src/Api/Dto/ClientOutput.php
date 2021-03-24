<?php
namespace App\Api\Dto;



class ClientOutput
{
    protected $id;
    protected $name;
    protected $url;
    protected $quota = -1;
    protected $description;
    protected $isActive = true;
    protected $preScript;
    protected $postScript;
    protected $maxParallelJobs = 1;
    protected $owner;
    protected $sshArgs;
    protected $rsyncShortArgs;
    protected $rsyncLongArgs;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    /**
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }
    
    /**
     * @param integer $quota
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;
    }
    
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    
    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }
    
    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }
    
    /**
     * @return array
     */
    public function getPreScript()
    {
        return $this->preScript;
    }
    
    /**
     * @param array $preScript
     */
    public function setPreScript($preScript)
    {
        $this->preScript = $preScript;
    }
    
    /**
     * @return array
     */
    public function getPostScript()
    {
        return $this->postScript;
    }
    
    /**
     * @param array $postScript
     */
    public function setPostScript($postScript)
    {
        $this->postScript = $postScript;
    }
    
    /**
     * @return integer
     */
    public function getMaxParallelJobs()
    {
        return $this->maxParallelJobs;
    }
    
    /**
     * @param integer $maxParallelJobs
     */
    public function setMaxParallelJobs($maxParallelJobs)
    {
        $this->maxParallelJobs = $maxParallelJobs;
    }
    
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
     * @return string
     */
    public function getSshArgs()
    {
        return $this->sshArgs;
    }
    
    /**
     * @param string $sshArgs
     */
    public function setSshArgs($sshArgs)
    {
        $this->sshArgs = $sshArgs;
    }
    
    /**
     * @return string
     */
    public function getRsyncShortArgs()
    {
        return $this->rsyncShortArgs;
    }
    
    /**
     * @param string $rsyncShortArgs
     */
    public function setRsyncShortArgs($rsyncShortArgs)
    {
        $this->rsyncShortArgs = $rsyncShortArgs;
    }
    
    /**
     * @return string
     */
    public function getRsyncLongArgs()
    {
        return $this->rsyncLongArgs;
    }
    
    /**
     * @param string $rsyncLongArgs
     */
    public function setRsyncLongArgs($rsyncLongArgs)
    {
        $this->rsyncLongArgs = $rsyncLongArgs;
    }
    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    
}

