<?php
namespace App\Api\Dto;



class ClientOutput
{
    private $description;
    private $diskUsage = 0;
    private $id;
    private $isActive = true;
    private $maxParallelJobs = 1;
    private $name;
    private $owner;
    private $postScripts;
    private $preScripts;
    private $quota = -1;
    private $rsyncLongArgs;
    private $rsyncShortArgs;
    private $sshArgs;
    private $url;
    private $state;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return integer
     */
    public function getDiskUsage()
    {
        return $this->diskUsage;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return integer
     */
    public function getMaxParallelJobs()
    {
        return $this->maxParallelJobs;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return integer
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return array
     */
    public function getPostScripts()
    {
        return $this->postScripts;
    }

    /**
     * @return array
     */
    public function getPreScripts()
    {
        return $this->preScripts;
    }

    /**
     * Quota given in MiB
     * 
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }

    /**
     * @return string
     */
    public function getRsyncLongArgs()
    {
        return $this->rsyncLongArgs;
    }

    /**
     * @return string
     */
    public function getRsyncShortArgs()
    {
        return $this->rsyncShortArgs;
    }

    /**
     * @return string
     */
    public function getSshArgs()
    {
        return $this->sshArgs;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param integer $diskUsage
     */
    public function setDiskUsage($diskUsage)
    {
        $this->diskUsage = $diskUsage;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @param integer $maxParallelJobs
     */
    public function setMaxParallelJobs($maxParallelJobs)
    {
        $this->maxParallelJobs = $maxParallelJobs;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param integer $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param array $postScripts
     */
    public function setPostScripts($postScripts)
    {
        $this->postScripts = $postScripts;
    }

    /**
     * @param array $preScript
     */
    public function setPreScripts($preScripts)
    {
        $this->preScripts = $preScripts;
    }

    /**
     * @param integer $quota
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;
    }

    /**
     * @param string $rsyncLongArgs
     */
    public function setRsyncLongArgs($rsyncLongArgs)
    {
        $this->rsyncLongArgs = $rsyncLongArgs;
    }

    /**
     * @param string $rsyncShortArgs
     */
    public function setRsyncShortArgs($rsyncShortArgs)
    {
        $this->rsyncShortArgs = $rsyncShortArgs;
    }

    /**
     * @param string $sshArgs
     */
    public function setSshArgs($sshArgs)
    {
        $this->sshArgs = $sshArgs;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function setState($state)
    {
        $this->state = $state;
    }
}
