<?php

namespace Binovo\Tknika\TknikaBackupsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Job
{
    /**
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="jobs")
     */
    protected $client;

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
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Policy", inversedBy="jobs")
     */
    protected $policy;

    /**
     * @ORM\Column(type="text")
     */
    protected $postScript;

    /**
     * @ORM\Column(type="text")
     */
    protected $preScript;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $url;


    /**
     * Set description
     *
     * @param string $description
     * @return Job
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
     * @return Job
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
     * @return Job
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
     * @return Job
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
     * Set url
     *
     * @param string $url
     * @return Job
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
     * Set client
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Client $client
     * @return Job
     */
    public function setClient(\Binovo\Tknika\TknikaBackupsBundle\Entity\Client $client = null)
    {
        $this->client = $client;
    
        return $this;
    }

    /**
     * Get client
     *
     * @return Binovo\Tknika\TknikaBackupsBundle\Entity\Client 
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set policy
     *
     * @param Binovo\Tknika\TknikaBackupsBundle\Entity\Policy $policy
     * @return Job
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
}