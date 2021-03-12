<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace App\Entity;

use App\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use \RuntimeException;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class BackupLocation
{
    const QUOTA_UNLIMITED = -1;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $host = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $directory;

    /**
     * Parallel jobs allowed for the location
     *
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\Regex(
     *     pattern     = "/^[1-9]\d*$/i",
     *     htmlPattern = "^[1-9]\d*$",
     *     message="Max parallel jobs value must be a positive integer"
     * )
     */
    protected $maxParallelJobs = 1;
    
    /**
     * Get max parallel jobs
     *
     * @return integer
     */
    public function getMaxParallelJobs()
    {
        return $this->maxParallelJobs;
    }
    
    /**
     * Set max parallel jobs
     *
     * @param integer $maxParallelJobs
     * @return Client
     */
    public function setMaxParallelJobs($maxParallelJobs)
    {
        $this->maxParallelJobs = $maxParallelJobs;
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return BackupLocation
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get host
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set host
     * 
     * @param string $host
     * @return BackupLocation
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Get directory
     * 
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set directory
     * 
     * @param string $directory
     * @return BackupLocation
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Get effective directory
     * Returns the full path where the backup will be stored,
     * host and directory.
     *
     * @return string
     */
    public function getEffectiveDir()
    {
        if ("" == $this->getHost()) {
            return $this->getDirectory();
        } else {
            return sprintf('/net/%s%s',$this->getHost(), $this->getDirectory());
        }
    }
}
