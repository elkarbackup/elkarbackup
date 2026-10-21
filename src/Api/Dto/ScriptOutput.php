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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getIsClientPost()
    {
        return $this->isClientPost;
    }

    /**
     * @return bool
     */
    public function getIsClientPre()
    {
        return $this->isClientPre;
    }

    /**
     * @return bool
     */
    public function getIsJobPost()
    {
        return $this->isJobPost;
    }

    /**
     * @return bool
     */
    public function getIsJobPre()
    {
        return $this->isJobPre;
    }

    /**
     * @return bool
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param bool $isClientPost
     */
    public function setIsClientPost($isClientPost)
    {
        $this->isClientPost = $isClientPost;
    }

    /**
     * @param bool $isClientPre
     */
    public function setIsClientPre($isClientPre)
    {
        $this->isClientPre = $isClientPre;
    }

    /**
     * @param bool $isJobPost
     */
    public function setIsJobPost($isJobPost)
    {
        $this->isJobPost = $isJobPost;
    }

    /**
     * @param bool $isJobPre
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

