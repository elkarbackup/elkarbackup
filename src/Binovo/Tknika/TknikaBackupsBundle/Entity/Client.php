<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Entity;

use Binovo\Tknika\TknikaBackupsBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\OneToMany(targetEntity="Job", mappedBy="client")
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
     * @ORM\Column(type="text", nullable=true)
     */
    protected $preScript;
    protected $deletePreScriptFile = false;
    protected $preScriptFile;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $url;

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
        if ($this->deletePreScriptFile && file_exists($this->getScriptpath('pre'))) {
            if (!unlink($this->getScriptpath('pre'))) {
                throw new RuntimeException("Error removing file " . $this->getScriptpath('pre'));
            }
        }
        if (null !== $this->preScriptFile) {
            $this->preScriptFile->move($this->getScriptDirectory(), $this->getScriptName('pre'));
            unset($this->preScriptFile);
        }
        if ($this->deletePostScriptFile && file_exists($this->getScriptpath('post'))) {
            if (!unlink($this->getScriptpath('post'))) {
                throw new RuntimeException("Error removing file " . $this->getScriptpath('post'));
            }
        }
        if (null !== $this->postScriptFile) {
            $this->postScriptFile->move($this->getScriptDirectory(), $this->getScriptName('post'));
            unset($this->postScriptFile);
        }
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if (file_exists($this->getScriptpath('pre'))) {
            if (!unlink($this->getScriptpath('pre'))) {
                throw new RuntimeException("Error removing file " . $this->getScriptpath('pre'));
            }
        }
        if (file_exists($this->getScriptpath('post'))) {
            if (!unlink($this->getScriptpath('post'))) {
                throw new RuntimeException("Error removing file " . $this->getScriptpath('post'));
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
        return sprintf('%s_%04d.bin', $scriptType, $this->getId());
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
     * @return Client
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
}