<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Entity;

use \DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity
 */
class Queue
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Job")
     */
    protected $job;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $date;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $runningSince;
    
    public function __construct($job = null)
    {
        $this->date = new DateTime();
        $this->job = $job;
    }
    
    /**
     * Get job
     * @return Binovo\ElkarBackupBundle\Entity\Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set job
     * 
     * @param Binovo\ElkarBackupBundle\Entity\Job $job
     * @return Queue
     */
    public function setJob($job)
    {
        $this->job = $job;
    }
    
    /**
     * Get date
     *
     * @return datetime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set date
     *
     * @param datetime $date
     * @return Queue
     */
    public function setDate($date)
    {
        $this->date = $date;
    }
    
    /**
     * Get running since
     *
     * @return datetime
     */
    public function getRunningSince()
    {
        return $this->runningSince;
    }

    /**
     * Set running since
     *
     * @param datetime $runningSince
     * @return Queue
     */
    public function setRunningSince($runningSince)
    {
        $this->runningSince = $runningSince;
    }
}