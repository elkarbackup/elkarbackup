<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Retain
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $dayOfMonth;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $dayOfWeek;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $hour;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $minute;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $month;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Policy", inversedBy="retains")
     */
    protected $policy;

    /**
     * @ORM\OneToMany(targetEntity="RuntimeRetain", mappedBy="retain")
     */
    protected $runtimeRetains;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->runtimeRetains = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set dayOfMonth
     *
     * @param string $dayOfMonth
     * @return Retain
     */
    public function setDayOfMonth($dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;
    
        return $this;
    }

    /**
     * Get dayOfMonth
     *
     * @return string 
     */
    public function getDayOfMonth()
    {
        return $this->dayOfMonth;
    }

    /**
     * Set dayOfWeek
     *
     * @param string $dayOfWeek
     * @return Retain
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;
    
        return $this;
    }

    /**
     * Get dayOfWeek
     *
     * @return string 
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Retain
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
     * Set hour
     *
     * @param string $hour
     * @return Retain
     */
    public function setHour($hour)
    {
        $this->hour = $hour;
    
        return $this;
    }

    /**
     * Get hour
     *
     * @return string 
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
     * @param string $minute
     * @return Retain
     */
    public function setMinute($minute)
    {
        $this->minute = $minute;
    
        return $this;
    }

    /**
     * Get minute
     *
     * @return string 
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Set month
     *
     * @param string $month
     * @return Retain
     */
    public function setMonth($month)
    {
        $this->month = $month;
    
        return $this;
    }

    /**
     * Get month
     *
     * @return string 
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Retain
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
     * Set policy
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Policy $policy
     * @return Retain
     */
    public function setPolicy(\Binovo\Tknika\TknikaBackupsBundle\Entity\Policy $policy = null)
    {
        $this->policy = $policy;
    
        return $this;
    }

    /**
     * Get policy
     *
     * @return Binovo\Tknika\TknikaBackupsBundle\Entity\Policy 
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * Add runtimeRetains
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\RuntimeRetain $runtimeRetains
     * @return Retain
     */
    public function addRuntimeRetain(\Binovo\Tknika\TknikaBackupsBundle\Entity\RuntimeRetain $runtimeRetains)
    {
        $this->runtimeRetains[] = $runtimeRetains;
    
        return $this;
    }

    /**
     * Remove runtimeRetains
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\RuntimeRetain $runtimeRetains
     */
    public function removeRuntimeRetain(\Binovo\Tknika\TknikaBackupsBundle\Entity\RuntimeRetain $runtimeRetains)
    {
        $this->runtimeRetains->removeElement($runtimeRetains);
    }

    /**
     * Get runtimeRetains
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getRuntimeRetains()
    {
        return $this->runtimeRetains;
    }
}