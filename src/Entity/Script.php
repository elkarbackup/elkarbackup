<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Entity;

use \RuntimeException;
use Binovo\ElkarBackupBundle\Lib\Globals;
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
     * @ORM\ManyToMany(targetEntity="Client", mappedBy="postScripts")
     */
    protected $postClients;

    /**
     * @ORM\ManyToMany(targetEntity="Job", mappedBy="postScripts")
     */
    protected $postJobs;

    /**
     * @ORM\ManyToMany(targetEntity="Client", mappedBy="preScripts")
     */
    protected $preClients;

    /**
     * @ORM\ManyToMany(targetEntity="Job", mappedBy="preScripts")
     */
    protected $preJobs;

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

    /**
     * Add postClients
     *
     * @param Binovo\ElkarBackupBundle\Entity\Client $postClients
     * @return Script
     */
    public function addPostClient(Client $postClients)
    {
        $this->postClients[] = $postClients;
        return $this;
    }

    /**
     * Remove postClients
     *
     * @param Binovo\ElkarBackupBundle\Entity\Client $postClients
     */
    public function removePostClient(Client $postClients)
    {
        $this->postClients->removeElement($postClients);
    }

    /**
     * Get postClients
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPostClients()
    {
        return $this->postClients;
    }

    /**
     * Add postJobs
     *
     * @param Binovo\ElkarBackupBundle\Entity\Job $postJobs
     * @return Script
     */
    public function addPostJob(Job $postJobs)
    {
        $this->postJobs[] = $postJobs;
        return $this;
    }

    /**
     * Remove postJobs
     *
     * @param Binovo\ElkarBackupBundle\Entity\Job $postJobs
     */
    public function removePostJob(Job $postJobs)
    {
        $this->postJobs->removeElement($postJobs);
    }

    /**
     * Get postJobs
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPostJobs()
    {
        return $this->postJobs;
    }

    /**
     * Add preClients
     *
     * @param Binovo\ElkarBackupBundle\Entity\Client $preClients
     * @return Script
     */
    public function addPreClient(Client $preClients)
    {
        $this->preClients[] = $preClients;
        return $this;
    }

    /**
     * Remove preClients
     *
     * @param Binovo\ElkarBackupBundle\Entity\Client $preClients
     */
    public function removePreClient(Client $preClients)
    {
        $this->preClients->removeElement($preClients);
    }

    /**
     * Get preClients
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPreClients()
    {
        return $this->preClients;
    }

    /**
     * Add preJobs
     *
     * @param Binovo\ElkarBackupBundle\Entity\Job $preJobs
     * @return Script
     */
    public function addPreJob(Job $preJobs)
    {
        $this->preJobs[] = $preJobs;
        return $this;
    }

    /**
     * Remove preJobs
     *
     * @param Binovo\ElkarBackupBundle\Entity\Job $preJobs
     */
    public function removePreJob(Job $preJobs)
    {
        $this->preJobs->removeElement($preJobs);
    }

    /**
     * Get preJobs
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getPreJobs()
    {
        return $this->preJobs;
    }

    public function isUsed()
    {
        $postClients = $this->getPostClients();
        $postJobs    = $this->getPostJobs();
        $preClients  = $this->getPreClients();
        $preJobs     = $this->getPreJobs();

        return !empty($postClients) && $postClients->count() != 0 ||
               !empty($postJobs)    && $postJobs->count()    != 0 ||
               !empty($preClients)  && $preClients->count()  != 0 ||
               !empty($preJobs)     && $preJobs->count()     != 0;
    }
}