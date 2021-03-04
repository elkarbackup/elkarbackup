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
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $priority;
    
    /**
     * Variable to show the state in the queue
     *
     * @ORM\Column(type="string",length=255, nullable=false)
     */
    protected $state;
    
    /**
     * Variable to show if the task is aborted and its state
     *
     * @ORM\Column(type="boolean")
     */
    protected $aborted;
    
    /**
     * Data generated during the execution
     *
     * @ORM\Column(type="text")
     */
    protected $data;

    public function __construct($job = null)
    {
        $this->date = new DateTime();
        $this->job = $job;
        $this->priority = $job->getPriority();
        $this->state = 'QUEUED';
        $this->aborted = false;
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
    
    /**
     * Get priority
     *
     * @return Queue
     */
    public function getPriority()
    {
        return $this->priority;
    }
    
    /**
     * Set priority
     *
     * @param integer $priority
     * 
     * @return Queue
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }
    
    /**
     * Get State
     * 
     * @return Queue
     */
    public function getState()
    {
        return $this->state;
    }
    
    /**
     * Set State
     * 
     * @param string $state
     * 
     * @return Queue
     */
    public function setState($state)
    {
        $this->state = $state;
    }
    
    /**
     * Get aborted
     * 
     * @return Queue
     */
    public function getAborted()
    {
        return $this->aborted;
    }
    
    /**
     * Set aborted
     * 
     * @param boolean $aborted
     * 
     * @return Queue
     */
    public function setAborted($aborted)
    {
        $this->aborted = $aborted;
    }
    
    /**
     * Get data
     * 
     * @return array
     */
    public function getData()
    {
        $decodedData = json_decode($this->data, true);
        return $decodedData;;
    }
    
    /**
     * Set data
     * 
     * @param array $data
     * 
     * @return Queue
     */
    public function setData($data)
    {
        $this->data = json_encode($data);
    }
}