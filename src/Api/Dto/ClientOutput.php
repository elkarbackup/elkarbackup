<?php
namespace App\Api\Dto;



class ClientOutput
{
    private $description;
    private $id;
    private $isActive = true;
    private $maxParallelJobs = 1;
    private $name;
    private $owner;
    private $postScript;
    private $preScript;
    private $quota = -1;
    private $rsyncLongArgs;
    private $rsyncShortArgs;
    private $sshArgs;
    private $url;

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
    public function getPostScript()
    {
        return $this->postScript;
    }

    /**
     * @return array
     */
    public function getPreScript()
    {
        return $this->preScript;
    }

    /**
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
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * @param array $postScript
     */
    public function setPostScript($postScript)
    {
        $this->postScript = $postScript;
    }

    /**
     * @param array $preScript
     */
    public function setPreScript($preScript)
    {
        $this->preScript = $preScript;
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
}
