<?php
namespace App\Api\Dto;

class ScriptOutput
{
    private $description;
    private $id;
    private $isClientPost;
    private $isClientPre;
    private $isJobPost;
    private $isJobPre;
    private $name;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function getIsClientPost()
    {
        return $this->isClientPost;
    }

    /**
     * @return boolean
     */
    public function getIsClientPre()
    {
        return $this->isClientPre;
    }

    /**
     * @return boolean
     */
    public function getIsJobPost()
    {
        return $this->isJobPost;
    }

    /**
     * @return boolean
     */
    public function getIsJobPre()
    {
        return $this->isJobPre;
    }

    /**
     * @return boolean
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param boolean $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param boolean $isClientPost
     */
    public function setIsClientPost($isClientPost)
    {
        $this->isClientPost = $isClientPost;
    }

    /**
     * @param boolean $isClientPre
     */
    public function setIsClientPre($isClientPre)
    {
        $this->isClientPre = $isClientPre;
    }

    /**
     * @param boolean $isJobPost
     */
    public function setIsJobPost($isJobPost)
    {
        $this->isJobPost = $isJobPost;
    }

    /**
     * @param boolean $isJobPre
     */
    public function setIsJobPre($isJobPre)
    {
        $this->isJobPre = $isJobPre;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}

