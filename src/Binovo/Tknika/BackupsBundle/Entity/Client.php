<?php

namespace Binovo\Tknika\BackupsBundle\Entity;

use Binovo\Tknika\BackupsBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use \RuntimeException;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Client
{
    const QUOTA_UNLIMITED = -1;

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
     * @ORM\OneToMany(targetEntity="Job", mappedBy="client", cascade={"remove"})
     */
    protected $jobs;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;


    /**
     * @ORM\ManyToMany(targetEntity="Script", inversedBy="postClients")
     * @ORM\JoinTable(name="ClientScriptPost")
     */
    protected $postScripts;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $url;

    /**
     * @ORM\ManyToMany(targetEntity="Script", inversedBy="preClients")
     * @ORM\JoinTable(name="ClientScriptPre")
     */
    protected $preScripts;

    /**
     * Quota in KB. -1 means no limit, which is the default.
     *
     * @ORM\Column(type="integer")
     */
    protected $quota = self::QUOTA_UNLIMITED;

    /**
     * Helper variable to store the LogEntry to show on screen,
     * typically the last log LogRecord related to this client.
     */
    protected $logEntry = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Returns the full path of the snapshot directory
     */
    public function getSnapshotRoot()
    {
        return Globals::getSnapshotRoot($this->getId());
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Client
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
     * @return Client
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
     * Set url
     *
     * @param string $url
     * @return Job
     */
    public function setUrl($url)
    {
        if (isset($url)) {
            $this->url = $url;
        } else {
            $this->url = '';
        }

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Add jobs
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Job $jobs
     * @return Client
     */
    public function addJob(\Binovo\Tknika\BackupsBundle\Entity\Job $jobs)
    {
        $this->jobs[] = $jobs;

        return $this;
    }

    /**
     * Remove jobs
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Job $jobs
     */
    public function removeJob(\Binovo\Tknika\BackupsBundle\Entity\Job $jobs)
    {
        $this->jobs->removeElement($jobs);
    }

    /**
     * Get jobs
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Client
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
     * Get diskUsage
     *
     * @return integer
     */
    public function getDiskUsage()
    {
        $du = 0;
        foreach ($this->jobs as $job) {
            $du += $job->getDiskUsage();
        }
        return $du;
    }

    /**
     * Set quota
     *
     * @param integer $quota
     * @return Client
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;
        return $this;
    }

    /**
     * Get quota
     *
     * @return integer
     */
    public function getQuota()
    {
        return $this->quota;
    }

    /**
     * Add postScripts
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Script $postScripts
     * @return Client
     */
    public function addPostScript(Script $postScripts)
    {
        $this->postScripts[] = $postScripts;
        return $this;
    }

    /**
     * Remove postScripts
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Script $postScripts
     */
    public function removePostScript(Script $postScripts)
    {
        $this->postScripts->removeElement($postScripts);
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
     * Add preScripts
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Script $preScripts
     * @return Client
     */
    public function addPreScript(Script $preScripts)
    {
        $this->preScripts[] = $preScripts;
        return $this;
    }

    /**
     * Remove preScripts
     *
     * @param Binovo\Tknika\BackupsBundle\Entity\Script $preScripts
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
}