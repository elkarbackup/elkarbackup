<?php

namespace Binovo\Tknika\BackupsBundle\Entity;

use Binovo\Tknika\BackupsBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use \RuntimeException;

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
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if ($this->scriptFile) {
            $this->deleteScriptFile = true;
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if ($this->deleteScriptFile && file_exists($this->getScriptPath())) {
            if (!unlink($this->getScriptPath())) {
                throw new RuntimeException("Error removing file " . $this->getScriptPath());
            }
        }
        if (null !== $this->scriptFile) {
            $this->scriptFile->move($this->getScriptDirectory(), $this->getScriptName());
            if (!chmod($this->getScriptPath(), 0755)) {
                throw new RuntimeException("Error setting file permission " . $this->getScriptPath());
            }
            unset($this->scriptFile);
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

    /**
     * Get scriptFile
     *
     * @return string
     */
    public function getScriptFile()
    {
        return $this->scriptFile;
    }
}