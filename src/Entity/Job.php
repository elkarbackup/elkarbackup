<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Api\Dto\JobInput;
use App\Api\Dto\JobOutput;
use App\Api\Filter\JobByNameFilter;
use App\Api\Filter\JobClientFilter;
use App\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use Monolog\Logger;
use \RuntimeException;

/**
 * @ApiResource(
 *     input  = JobInput::class,
 *     output = JobOutput::class,
 *     normalizationContext={
 *         "skip_null_values" = false
 *     },
 *     collectionOperations= {"get", "post"},
 *     itemOperations= {"get", "put", "delete"},
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Job
{
    const NOTIFY_TO_ADMIN = 'admin';
    const NOTIFY_TO_OWNER = 'owner';
    const NOTIFY_TO_EMAIL = 'email';

    const NOTIFICATION_LEVEL_ALL     = 0;
    const NOTIFICATION_LEVEL_INFO    = Logger::INFO;
    const NOTIFICATION_LEVEL_WARNING = Logger::WARNING;
    const NOTIFICATION_LEVEL_ERROR   = Logger::ERROR;
    const NOTIFICATION_LEVEL_NONE    = 1000;

    /**
     * @ApiFilter(JobClientFilter::class)
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="jobs")
     */
    protected $client;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive = true;

    /**
     * @ApiFilter(JobByNameFilter::class)
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $notificationsEmail;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $notificationsTo = '["owner"]';

    /**
     * @ORM\Column(type="integer")
     */
    protected $minNotificationLevel = self::NOTIFICATION_LEVEL_ERROR;

    /**
     * Include expressions
     * @ORM\Column(type="text", nullable=true)
     */
    protected $include;

    /**
     * Exclude expressions
     * @ORM\Column(type="text", nullable=true)
     */
    protected $exclude;

    /**
     * @ORM\ManyToOne(targetEntity="Policy")
     */
    protected $policy;

    /**
     * @ORM\ManyToMany(targetEntity="Script", inversedBy="postJobs")
     * @ORM\JoinTable(name="JobScriptPost")
     */
    protected $postScripts;

    /**
     * @ORM\ManyToMany(targetEntity="Script", inversedBy="preJobs")
     * @ORM\JoinTable(name="JobScriptPre")
     */
    protected $preScripts;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * Disk usage in KB.
     *
     * @ORM\Column(type="bigint")
     */
    protected $diskUsage = 0;

    /**
     * Priority. Lower numbered jobs run first. Set to 2**31-1 for newly
     * created jobs so that they will run last.
     *
     * @ORM\Column(type="integer")
     */
    protected $priority = 2147483647;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $useLocalPermissions = true;

    /**
     * Helper variable to store the LogEntry to show on screen,
     * typically the last log LogRecord related to this client.
     */
    protected $logEntry = null;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * Job lastResult: ok, fail
     */
    protected $lastResult = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * Security token for remote management
     */
    protected $token = null;

    /**
     * @ORM\ManyToOne(targetEntity="BackupLocation")
     */
    protected $backupLocation;

    /**
     * Returns the full path of the snapshot directory
     */
    public function getSnapshotRoot()
    {
        return Globals::getSnapshotRoot($this->getClient()->getId(), $this);
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Job
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Job
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Job
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        $clientUrl = $this->client->getUrl();
        if (empty($clientUrl)) {

            return $this->path;
        } else {
          // return url without ssh_args
          if (strpos($clientUrl, 'ssh_args') !== false) {
            $clientUrl = explode(" ", $clientUrl)[0];
          }
          return sprintf("%s:%s", $clientUrl, $this->path);
        }
    }

    /**
     * Set client
     *
     * @param App\Entity\Client $client
     * @return Job
     */
    public function setClient(\App\Entity\Client $client = null)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return App\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set include
     *
     * @param string $include
     * @return Policy
     */
    public function setInclude($include)
    {
        $this->include = $include;

        return $this;
    }

    /**
     * Get include
     *
     * If the include list of the job is empty fetches the exclude list of the policy.
     *
     * @return string
     */
    public function getInclude()
    {
        $include = '';
        if (!empty($this->include)) {
            $include = $this->include;
        } else if ($this->policy) {
            $include = $this->policy->getInclude();
        }

        return $include;
    }

    /**
     * Set exclude
     *
     * @param string $exclude
     * @return Policy
     */
    public function setExclude($exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Get exclude.
     *
     * If the exclude list of the job is empty fetches the exclude list of the policy.
     *
     * @return string
     */
    public function getExclude()
    {
        $exclude = '';
        if (!empty($this->exclude)) {
            $exclude = $this->exclude;
        } else if ($this->policy) {
            $exclude = $this->policy->getExclude();
        }

        return $exclude;
    }

    /**
     * Set policy
     *
     * @param App\Entity\Policy $policy
     * @return Job
     */
    public function setPolicy(\App\Entity\Policy $policy = null)
    {
        $this->policy = $policy;

        return $this;
    }

    /**
     * Get policy
     *
     * @return App\Entity\Policy
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Job
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }


    /**
     * Set notificationsTo
     *
     * @param string $notificationsTo
     * @return Job
     */
    public function setNotificationsTo($notificationsTo)
    {
        $this->notificationsTo = json_encode(array_values($notificationsTo));

        return $this;
    }

    /**
     * Get notificationsTo
     *
     * @return string
     */
    public function getNotificationsTo()
    {
        return json_decode($this->notificationsTo, true);
    }

    /**
     * Set notificationsEmail
     *
     * @param string $notificationsEmail
     * @return Job
     */
    public function setNotificationsEmail($notificationsEmail)
    {
        $this->notificationsEmail = $notificationsEmail;

        return $this;
    }

    /**
     * Get notificationsEmail
     *
     * @return string
     */
    public function getNotificationsEmail()
    {
        return $this->notificationsEmail;
    }

    /**
     * Set minNotificationLevel
     *
     * @param integer $minNotificationLevel
     * @return Job
     */
    public function setMinNotificationLevel($minNotificationLevel)
    {
        $this->minNotificationLevel = $minNotificationLevel;

        return $this;
    }

    /**
     * Get minNotificationLevel
     *
     * @return integer
     */
    public function getMinNotificationLevel()
    {
        return $this->minNotificationLevel;
    }

    /**
     * Set LogEntry
     *
     * @param LogRecord $LogEntry
     * @return Client
     */
    public function setLogEntry(LogRecord $logEntry = null)
    {
        $this->logEntry = $logEntry;

        return $this;
    }

    /**
     * Get LogEntry
     *
     * @return LogRecord
     */
    public function getLogEntry()
    {
        return $this->logEntry;
    }

    /**
     * Set diskUsage
     *
     * @param bigint $diskUsage
     * @return Job
     */
    public function setDiskUsage($diskUsage)
    {
        $this->diskUsage = $diskUsage;

        return $this;
    }

    /**
     * Get diskUsage
     *
     * @return bigint
     */
    public function getDiskUsage()
    {
        return $this->diskUsage;
    }

    /**
     * Set Priority
     *
     * @param integer $Priority
     * @return Job
     */
    public function setPriority($Priority)
    {
        $this->priority = $Priority;

        return $this;
    }

    /**
     * Get Priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set useLocalPermissions
     *
     * @param boolean $useLocalPermissions
     * @return Job
     */
    public function setUseLocalPermissions($useLocalPermissions)
    {
        $this->useLocalPermissions = $useLocalPermissions;
        return $this;
    }

    /**
     * Get useLocalPermissions
     *
     * @return boolean
     */
    public function getUseLocalPermissions()
    {
        return $this->useLocalPermissions;
    }
    public function __construct()
    {
        $this->postScripts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->preScripts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add postScript
     *
     * @param App\Entity\Script $postScript
     * @return Job
     */
    public function addPostScript(Script $postScript)
    {
        $this->postScripts[] = $postScript;
        return $this;
    }

    /**
     * Remove postScript
     *
     * @param App\Entity\Script $postScript
     */
    public function removePostScript(Script $postScript)
    {
        $this->postScripts->removeElement($postScript);
    }

    /**
     * Add preScripts
     *
     * @param App\Entity\Script $preScripts
     * @return Job
     */
    public function addPreScript(Script $preScripts)
    {
        $this->preScripts[] = $preScripts;
        return $this;
    }

    /**
     * Remove preScripts
     *
     * @param App\Entity\Script $preScripts
     */
    public function removePreScript(Script $preScripts)
    {
        $this->preScripts->removeElement($preScripts);
    }

    /**
     * Get preScripts
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPreScripts()
    {
        return $this->preScripts;
    }

    /**
     * Get postScripts
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPostScripts()
    {
        return $this->postScripts;
    }

    /**
     * Set last result
     *
     * @param string $lastResult
     *
     * @return Job
     */
    public function setLastResult($lastResult)
    {
        $this->lastResult = $lastResult;

        return $this;
    }

    /**
     * Get last result
     *
     * @return string
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Job
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get backupLocation
     * @return App\Entity\BackupLocation
     */
    public function getBackupLocation()
    {
        return $this->backupLocation;
    }

    /**
     * Set backupLocation
     * 
     * @param App\Entity\BackupLocation $backupLocation
     * @return Job
     */
    public function setBackupLocation($backupLocation)
    {
        $this->backupLocation = $backupLocation;
    }
}
