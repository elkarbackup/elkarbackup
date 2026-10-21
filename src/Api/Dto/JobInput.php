<?php
namespace App\Api\Dto;

class JobInput
{
    private $backupLocation = 1;
    private $client;
    private $description;
    private $exclude;
    private $id;
    private $include;
    private $isActive = true;
    private $minNotificationLevel = 400;
    private $name;
    private $notificationsEmail;
    private $notificationsTo = ["owner"];
    private $path;
    private $policy = 1;
    private $postScripts = [];
    private $preScripts = [];
    private $token = null;
    private $useLocalPermissions = true;
    /**
     * @return int
     */
    public function getBackupLocation(): int
    {
        return $this->backupLocation;
    }
    
    /**
     * @return int
     */
    public function getClient(): int
    {
        return $this->client;
    }
    
    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    /**
     * @return string|null
     */
    public function getExclude(): ?string
    {
        return $this->exclude;
    }
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @return string|null
     */
    public function getInclude(): ?string
    {
        return $this->include;
    }
    
    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
    }
    
    /**
     * @return int
     */
    public function getMinNotificationLevel(): int
    {
        return $this->minNotificationLevel;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @return string|null
     */
    public function getNotificationsEmail(): ?string
    {
        return $this->notificationsEmail;
    }
    
    /**
     * @return array
     */
    public function getNotificationsTo(): array
    {
        return $this->notificationsTo;
    }
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * @return int
     */
    public function getPolicy(): int
    {
        return $this->policy;
    }
    
    /**
     * @return array|null
     */
    public function getPostScripts(): ?array
    {
        return $this->postScripts;
    }
    
    /**
     * @return array|null
     */
    public function getPreScripts(): ?array
    {
        return $this->preScripts;
    }
    
    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }
    
    /**
     * @return bool
     */
    public function getUseLocalPermissions(): bool
    {
        return $this->useLocalPermissions;
    }
    
    /**
     * @param int $backupLocation
     */
    public function setBackupLocation(int $backupLocation): void
    {
        $this->backupLocation = $backupLocation;
    }
    
    /**
     * @param int $client
     */
    public function setClient(int $client): void
    {
        $this->client = $client;
    }
    
    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
    
    /**
     * @param string|null $exclude
     */
    public function setExclude(?string $exclude): void
    {
        $this->exclude = $exclude;
    }
    
    /**
     * @param string|null $include
     */
    public function setInclude(?string $include): void
    {
        $this->include = $include;
    }
    
    /**
     * @param bool $isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
    
    /**
     * @param int $minNotificationLevel
     */
    public function setMinNotificationLevel(int $minNotificationLevel): void
    {
        $this->minNotificationLevel = $minNotificationLevel;
    }
    
    /**
     * @param string $name
     */
    public function setName(string $name):void
    {
        $this->name = $name;
    }
    
    /**
     * @param string|null $notificationsEmail
     */
    public function setNotificationsEmail(?string $notificationsEmail): void
    {
        $this->notificationsEmail = $notificationsEmail;
    }
    
    /**
     * @param array  $notificationsTo
     */
    public function setNotificationsTo(array $notificationsTo): void
    {
        $this->notificationsTo = $notificationsTo;
    }
    
    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }
    
    /**
     * @param int $policy
     */
    public function setPolicy(int $policy): void
    {
        $this->policy = $policy;
    }
    
    /**
     * @param array: $postScripts
     */
    public function setPostScripts(?array $postScripts): void
    {
        $this->postScripts = $postScripts;
    }
    
    /**
     * @param array: $preScripts
     */
    public function setPreScripts(?array $preScripts): void
    {
        $this->preScripts = $preScripts;
    }
    
    /**
     * @param string|null $token
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
    }
    
    /**
     * @param bool $useLocalPermissions
     */
    public function setUseLocalPermissions(bool $useLocalPermissions): void
    {
        $this->useLocalPermissions = $useLocalPermissions;
    }
    
}

