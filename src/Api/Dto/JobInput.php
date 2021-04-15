<?php
namespace App\Api\Dto;

class JobInput
{
    protected $client;
    protected $description;
    protected $id;
    protected $isActive = true;
    protected $name;
    protected $notificationsEmail;
    protected $notificationsTo = '["owner"]';
    protected $minNotificationLevel = 400;
    protected $include;
    protected $exclude;
    protected $policy = 1;
    protected $postScripts = [];
    protected $preScripts = [];
    protected $path;
    protected $useLocalPermissions = true;
    protected $token = null;
    protected $backupLocation = 1;
    
    /**
     * @return integer
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param integer $client
     */
    public function setClient($client)
    {
        $this->client = $client;
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
    public function getNotificationsEmail()
    {
        return $this->notificationsEmail;
    }

    /**
     * @param string $notificationsEmail
     */
    public function setNotificationsEmail($notificationsEmail)
    {
        $this->notificationsEmail = $notificationsEmail;
    }

    /**
     * @return array
     */
    public function getNotificationsTo()
    {
        return $this->notificationsTo;
    }

    /**
     * @param array $notificationsTo
     */
    public function setNotificationsTo($notificationsTo)
    {
        $this->notificationsTo = $notificationsTo;
    }

    /**
     * @return integer
     */
    public function getMinNotificationLevel()
    {
        return $this->minNotificationLevel;
    }

    /**
     * @param integer $minNotificationLevel
     */
    public function setMinNotificationLevel($minNotificationLevel)
    {
        $this->minNotificationLevel = $minNotificationLevel;
    }

    /**
     * @return string
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * @param string $include
     */
    public function setInclude($include)
    {
        $this->include = $include;
    }

    /**
     * @return string
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * @param string $exclude
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;
    }

    /**
     * @return integer
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * @param integer $policy
     */
    public function setPolicy($policy)
    {
        $this->policy = $policy;
    }

    /**
     * @return array
     */
    public function getPostScripts()
    {
        return $this->postScripts;
    }

    /**
     * @param array $postScripts
     */
    public function setPostScripts($postScripts)
    {
        $this->postScripts = $postScripts;
    }

    /**
     * @return array
     */
    public function getPreScripts()
    {
        return $this->preScripts;
    }

    /**
     * @param array $preScripts
     */
    public function setPreScripts($preScripts)
    {
        $this->preScripts = $preScripts;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return boolean
     */
    public function getUseLocalPermissions()
    {
        return $this->useLocalPermissions;
    }

    /**
     * @param boolean $useLocalPermissions
     */
    public function setUseLocalPermissions($useLocalPermissions)
    {
        $this->useLocalPermissions = $useLocalPermissions;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return integer
     */
    public function getBackupLocation()
    {
        return $this->backupLocation;
    }

    /**
     * @param integer $backupLocation
     */
    public function setBackupLocation($backupLocation)
    {
        $this->backupLocation = $backupLocation;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

}

