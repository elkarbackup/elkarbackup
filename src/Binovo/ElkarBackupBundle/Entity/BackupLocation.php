<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Entity;

use Binovo\ElkarBackupBundle\Lib\Globals;
use Doctrine\ORM\Mapping as ORM;
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
     * @ORM\Column(type="string", length=255)
     */
    protected $host = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $directory;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $tahoe;

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
     * Get tahoe
     * 
     * @return boolean
     */
    public function getTahoe()
    {
        return $this->tahoe;
    }

    /**
     * Set tahoe
     * 
     * @param boolean $tahoe
     * @return BackupLocation
     */
    public function setTahoe($tahoe)
    {
        $this->tahoe = $tahoe;
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
