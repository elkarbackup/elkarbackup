<?php

namespace Binovo\Tknika\BackupsBundle\Entity;

use \RuntimeException;
use Binovo\Tknika\BackupsBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Script
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
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    protected $deleteScriptFile = false;

    /**
     * @Assert\File(maxSize="6000000")
     */
    protected $scriptFile;

    /**
     * Helper variable to remember the script time for PostRemove actions
     */
    protected $filesToRemove;

    /**
     * True if can run before the client
     * @ORM\Column(type="boolean")
     */
    protected $isClientPre;

    /**
     * True if can run before the job
     * @ORM\Column(type="boolean")
     */
    protected $isJobPre;

    /**
     * True if can run after the client
     * @ORM\Column(type="boolean")
     */
    protected $isClientPost;

    /**
     * True if can run after the job
     * @ORM\Column(type="boolean")
     */
    protected $isJobPost;


    /**
     * True if can run after the job
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastUpdated;


    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if ($this->scriptFile) {
            if (file_exists($this->getScriptPath()) && !unlink($this->getScriptPath())) {
                throw new RuntimeException("Error removing file " . $this->getScriptPath());
            }
            $this->scriptFile->move($this->getScriptDirectory(), $this->getScriptName());
            if (!chmod($this->getScriptPath(), 0755)) {
                throw new RuntimeException("Error setting file permission " . $this->getScriptPath());
            }
        } else {
            if (!file_exists($this->getScriptPath())) {
                throw new RuntimeException("Trying to create script entity without script file. Aborting.");
            }
        }
    }

    /**
     * @ORM\PreRemove()
     */
    public function prepareRemoveUpload()
    {
        $this->filesToRemove = array($this->getScriptPath());
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

    public function getScriptPath()
    {
        return sprintf('%s/%s', $this->getScriptDirectory(), $this->getScriptName());
    }

    public function getScriptDirectory()
    {
        return Globals::getUploadDir();
    }

    public function getScriptName()
    {
        return sprintf('%04d.script', $this->getId());
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Script
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
     * @return Script
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
     * Set scriptFile
     *
     * @param string $scriptFile
     * @return Job
     */
    public function setScriptFile($scriptFile)
    {
        $this->scriptFile = $scriptFile;

        return $this;
    }

    public function getScriptFileExists()
    {
        return file_exists($this->getScriptPath());
    }

    /**
     * Get scriptFile
     *
     * @return string
     */
    public function getScriptFile()
    {
        return $this->scriptFile;
    }

    /**
     * Set isClientPre
     *
     * @param boolean $isClientPre
     * @return Script
     */
    public function setIsClientPre($isClientPre)
    {
        $this->isClientPre = $isClientPre;
        return $this;
    }

    /**
     * Get isClientPre
     *
     * @return boolean
     */
    public function getIsClientPre()
    {
        return $this->isClientPre;
    }

    /**
     * Set isJobPre
     *
     * @param boolean $isJobPre
     * @return Script
     */
    public function setIsJobPre($isJobPre)
    {
        $this->isJobPre = $isJobPre;
        return $this;
    }

    /**
     * Get isJobPre
     *
     * @return boolean
     */
    public function getIsJobPre()
    {
        return $this->isJobPre;
    }

    /**
     * Set isClientPost
     *
     * @param boolean $isClientPost
     * @return Script
     */
    public function setIsClientPost($isClientPost)
    {
        $this->isClientPost = $isClientPost;
        return $this;
    }

    /**
     * Get isClientPost
     *
     * @return boolean
     */
    public function getIsClientPost()
    {
        return $this->isClientPost;
    }

    /**
     * Set isJobPost
     *
     * @param boolean $isJobPost
     * @return Script
     */
    public function setIsJobPost($isJobPost)
    {
        $this->isJobPost = $isJobPost;
        return $this;
    }

    /**
     * Get isJobPost
     *
     * @return boolean
     */
    public function getIsJobPost()
    {
        return $this->isJobPost;
    }

    /**
     * Set lastUpdated
     *
     * @param datetime
     * @return Script
     */
    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    /**
     * Get lastUpdated
     *
     * @return datetime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }
}