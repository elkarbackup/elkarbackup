<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class RuntimeRetain
{
    /**
     * @ORM\Column(type="integer")
     */
    protected $dayOfMonth;

    /**
     * @ORM\Column(type="integer")
     */
    protected $dayOfWeek;

    /**
     * @ORM\Column(type="integer")
     */
    protected $hour;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $minute;

    /**
     * @ORM\Column(type="integer")
     */
    protected $month;

    /**
     * @ORM\ManyToOne(targetEntity="Retain", inversedBy="runtimeRetains")
     */
    protected $retain;

    /**
     * Set dayOfMonth
     *
     * @param integer $dayOfMonth
     * @return RuntimeRetain
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;
    
        return $this;
    }

    /**
     * Get dayOfMonth
     *
     * @return integer 
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * Set dayOfWeek
     *
     * @param integer $dayOfWeek
     * @return RuntimeRetain
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;
    
        return $this;
    }

    /**
     * Get dayOfWeek
     *
     * @return integer 
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * Set hour
     *
     * @param integer $hour
     * @return RuntimeRetain
     */
    public function setHour($hour)
    {
        $this->hour = $hour;
    
        return $this;
    }

    /**
     * Get hour
     *
     * @return integer 
     */
    public function getHour()
    {
        return $this->hour;
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
     * Set minute
     *
     * @param integer $minute
     * @return RuntimeRetain
     */
    public function setMinute($minute)
    {
        $this->minute = $minute;
    
        return $this;
    }

    /**
     * Get minute
     *
     * @return integer 
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Set month
     *
     * @param integer $month
     * @return RuntimeRetain
     */
    public function setMonth($month)
    {
        $this->month = $month;
    
        return $this;
    }

    /**
     * Get month
     *
     * @return integer 
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set retain
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Retain $retain
     * @return RuntimeRetain
     */
    public function setRetain(\Binovo\Tknika\TknikaBackupsBundle\Entity\Retain $retain = null)
    {
        $this->retain = $retain;
    
        return $this;
    }

    /**
     * Get retain
     *
     * @return Binovo\Tknika\TknikaBackupsBundle\Entity\Retain 
     */
    public function getRetain()
    {
        return $this->retain;
    }
}