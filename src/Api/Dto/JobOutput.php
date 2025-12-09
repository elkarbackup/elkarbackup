<?php
namespace App\Api\Dto;

class JobOutput
{
    private $backupLocation;
    private $client;
    private $description;
    private $diskUsage = 0;
    private $exclude;
    private $id;
    private $include;
    private $isActive = true;
    private $minNotificationLevel;
    private $name;
    private $notificationsEmail;
    private $notificationsTo = '["owner"]';
    private $path;
    private $policy;
    private $postScripts;
    private $preScripts;
    private $token = null;
    private $useLocalPermissions = true;
    private $lastResult = null;

    /**
     * @return integer
     */
    public function getBackupLocation()
    {
        return $this->backupLocation;
    }

    /**
     * @return integer
     */
    public function getClient()
    {
        return $this->client;
    }

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
     * @return string
     */
    public function getExclude()
    {
        return $this->exclude;
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
    public function getInclude()
    {
        return $this->include;
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
    public function getMinNotificationLevel()
    {
        return $this->minNotificationLevel;
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
    public function getNotificationsEmail()
    {
        return $this->notificationsEmail;
    }

    /**
     * @return array
     */
    public function getNotificationsTo()
    {
        return $this->notificationsTo;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return integer
     */
    public function getPolicy()
    {
        return $this->policy;
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
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return boolean
     */
    public function getUseLocalPermissions()
    {
        return $this->useLocalPermissions;
    }

    /**
     * @return string
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * @param integer $backupLocation
     */
    public function setBackupLocation($backupLocation)
    {
        $this->backupLocation = $backupLocation;
    }

    /**
     * @param integer $client
     */
    public function setClient($client)
    {
        $this->client = $client;
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
     * @param string $exclude
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $include
     */
    public function setInclude($include)
    {
        $this->include = $include;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @param integer $minNotificationLevel
     */
    public function setMinNotificationLevel($minNotificationLevel)
    {
        $this->minNotificationLevel = $minNotificationLevel;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $notificationsEmail
     */
    public function setNotificationsEmail($notificationsEmail)
    {
        $this->notificationsEmail = $notificationsEmail;
    }

    /**
     * @param array $notificationsTo
     */
    public function setNotificationsTo($notificationsTo)
    {
        $this->notificationsTo = $notificationsTo;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param integer $policy
     */
    public function setPolicy($policy)
    {
        $this->policy = $policy;
    }

    /**
     * @param array $postScripts
     */
    public function setPostScripts($postScripts)
    {
        $this->postScripts = $postScripts;
    }

    /**
     * @param array $preScripts
     */
    public function setPreScripts($preScripts)
    {
        $this->preScripts = $preScripts;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @param boolean $useLocalPermissions
     */
    public function setUseLocalPermissions($useLocalPermissions)
    {
        $this->useLocalPermissions = $useLocalPermissions;
    }

    /**
     * @param string $token
     */
    public function setLastResult($lastResult)
    {
        $this->lastResult = $lastResult;
    }
}

