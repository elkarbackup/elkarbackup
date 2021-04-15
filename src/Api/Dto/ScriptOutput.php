<?php
namespace App\Api\Dto;

class ScriptOutput
{
    protected $description;
    protected $id;
    protected $name;
    protected $isClientPre;
    protected $isJobPre;
    protected $isClientPost;
    protected $isJobPost;
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getIsClientPre()
    {
        return $this->isClientPre;
    }

    /**
     * @param mixed $isClientPre
     */
    public function setIsClientPre($isClientPre)
    {
        $this->isClientPre = $isClientPre;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getIsJobPre()
    {
        return $this->isJobPre;
    }

    /**
     * @param mixed $isJobPre
     */
    public function setIsJobPre($isJobPre)
    {
        $this->isJobPre = $isJobPre;
    }

    /**
     * @return mixed
     */
    public function getIsClientPost()
    {
        return $this->isClientPost;
    }

    /**
     * @param mixed $isClientPost
     */
    public function setIsClientPost($isClientPost)
    {
        $this->isClientPost = $isClientPost;
    }

    /**
     * @return mixed
     */
    public function getIsJobPost()
    {
        return $this->isJobPost;
    }

    /**
     * @param mixed $isJobPost
     */
    public function setIsJobPost($isJobPost)
    {
        $this->isJobPost = $isJobPost;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }


}

