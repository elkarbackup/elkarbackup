<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Policy
{
    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Job", mappedBy="policy")
     */
    protected $jobs;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Retain", mappedBy="policy")
     */
    protected $retains;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $url;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->jobs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->retains = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set description
     *
     * @param string $description
     * @return Policy
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
     * @return Policy
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
     * Set url
     *
     * @param string $url
     * @return Policy
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
     * @return Policy
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

    /**
     * Add retains
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Retain $retains
     * @return Policy
     */
    public function addRetain(\Binovo\Tknika\TknikaBackupsBundle\Entity\Retain $retains)
    {
        $this->retains[] = $retains;
    
        return $this;
    }

    /**
     * Remove retains
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Retain $retains
     */
    public function removeRetain(\Binovo\Tknika\TknikaBackupsBundle\Entity\Retain $retains)
    {
        $this->retains->removeElement($retains);
    }

    /**
     * Get retains
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getRetains()
    {
        return $this->retains;
    }
}