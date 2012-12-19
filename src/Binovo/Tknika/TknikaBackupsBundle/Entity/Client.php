<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Entity;

use Binovo\Tknika\TknikaBackupsBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use \RuntimeException;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Client
{
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
     * @ORM\Column(type="text", nullable=true)
     */
    protected $postScript;
    protected $deletePostScriptFile = false;
    protected $postScriptFile;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $url;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $preScript;
    protected $deletePreScriptFile = false;
    protected $preScriptFile;

    /**
     * @ORM\Column(type="integer")
     */
    protected $diskUsage = 0;

    /**
     * Helper variable to remember the script time for PostRemove actions
     */
    protected $filesToRemove;

    /**
     * Helper variable to store the LogEntry to show on screen,
     * typically the last log LogRecord related to this client.
     */
    protected $logEntry = null;

    private function isNewFileOrMustDeleteExistingFile($currentName, $file)
    {
        return null === $currentName || null !== $file;
    }

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
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if ($this->isNewFileOrMustDeleteExistingFile($this->preScript, $this->preScriptFile)) {
            $this->deletePreScriptFile = true;
        }
        if (null !== $this->preScriptFile) {
            $this->setPreScript($this->preScriptFile->getClientOriginalName());
        }
        if ($this->isNewFileOrMustDeleteExistingFile($this->postScript, $this->postScriptFile)) {
            $this->deletePostScriptFile = true;
        }
        if (null !== $this->postScriptFile) {
            $this->setPostScript($this->postScriptFile->getClientOriginalName());
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if ($this->deletePreScriptFile && file_exists($this->getScriptPath('pre'))) {
            if (!unlink($this->getScriptPath('pre'))) {
                throw new RuntimeException("Error removing file " . $this->getScriptPath('pre'));
            }
        }
        if (null !== $this->preScriptFile) {
            $this->preScriptFile->move($this->getScriptDirectory(), $this->getScriptName('pre'));
            if (!chmod($this->getScriptPath('pre'), 0755)) {
                throw new RuntimeException("Error setting file permission " . $this->getScriptPath('pre'));
            }
            unset($this->preScriptFile);
        }
        if ($this->deletePostScriptFile && file_exists($this->getScriptPath('post'))) {
            if (!unlink($this->getScriptPath('post'))) {
                throw new RuntimeException("Error removing file " . $this->getScriptPath('post'));
            }
        }
        if (null !== $this->postScriptFile) {
            $this->postScriptFile->move($this->getScriptDirectory(), $this->getScriptName('post'));
            if (!chmod($this->getScriptPath('post'), 0755)) {
                throw new RuntimeException("Error setting file permission " . $this->getScriptPath('post'));
            }
            unset($this->postScriptFile);
        }
    }

    /**
     * @ORM\PreRemove()
     */
    public function prepareRemoveUpload()
    {
        $this->filesToRemove = array($this->getScriptPath('pre'), $this->getScriptPath('post'));
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        foreach ($this->filesToRemove as $file) {
            if (file_exists($file)) {
                if (!Globals::delTree($file)) {
                    throw new RuntimeException("Error removing file " . $file);
                }
            }
        }
    }

    public function getScriptPath($scriptType)
    {
        return sprintf('%s/%s', $this->getScriptDirectory(), $this->getScriptName($scriptType));
    }

    public function getScriptDirectory()
    {
        return Globals::getUploadDir();
    }

    public function getScriptName($scriptType)
    {
        return sprintf('%04d.%s', $this->getId(), $scriptType);
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
     * Set postScript
     *
     * @param string $postScript
     * @return Client
     */
    public function setPostScript($postScript)
    {
        $this->postScript = $postScript;

        return $this;
    }

    /**
     * Get postScript
     *
     * @return string
     */
    public function getPostScript()
    {
        return $this->postScript;
    }

    /**
     * Set preScript
     *
     * @param string $preScript
     * @return Client
     */
    public function setPreScript($preScript)
    {
        $this->preScript = $preScript;

        return $this;
    }

    /**
     * Get preScript
     *
     * @return string
     */
    public function getPreScript()
    {
        return $this->preScript;
    }

    /**
     * Set preScriptFile
     *
     * @param string $preScriptFile
     * @return Job
     */
    public function setPreScriptFile($preScriptFile)
    {
        $this->preScriptFile = $preScriptFile;

        return $this;
    }

    /**
     * Get preScriptFile
     *
     * @return string
     */
    public function getPreScriptFile()
    {
        return $this->preScriptFile;
    }

    /**
     * Set postScriptFile
     *
     * @param string $postScriptFile
     * @return Job
     */
    public function setPostScriptFile($postScriptFile)
    {
        $this->postScriptFile = $postScriptFile;

        return $this;
    }

    /**
     * Get postScriptFile
     *
     * @return string
     */
    public function getPostScriptFile()
    {
        return $this->postScriptFile;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Job
     */
    public function setUrl($url)
    {
        $this->url = $url;

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
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Job $jobs
     * @return Client
     */
    public function addJob(\Binovo\Tknika\TknikaBackupsBundle\Entity\Job $jobs)
    {
        $this->jobs[] = $jobs;

        return $this;
    }

    /**
     * Remove jobs
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Job $jobs
     */
    public function removeJob(\Binovo\Tknika\TknikaBackupsBundle\Entity\Job $jobs)
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
     * Set diskUsage
     *
     * @param integer $diskUsage
     * @return Client
     */
    public function setDiskUsage($diskUsage)
    {
        $this->diskUsage = $diskUsage;
        return $this;
    }

    /**
     * Get diskUsage
     *
     * @return integer
     */
    public function getDiskUsage()
    {
        return $this->diskUsage;
    }
}