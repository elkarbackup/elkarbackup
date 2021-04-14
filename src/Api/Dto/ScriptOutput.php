<?php
namespace App\Api\Dto;

class ScriptOutput
{
    protected $description;
    protected $id;
    protected $name;
    protected $deleteScriptFile = false;
    protected $scriptFile;
    protected $filesToRemove;
    protected $isClientPre;
    protected $isJobPre;
    protected $isClientPost;
    protected $isJobPost;
    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function getDeleteScriptFile()
    {
        return $this->deleteScriptFile;
    }

    /**
     * @param boolean $deleteScriptFile
     */
    public function setDeleteScriptFile($deleteScriptFile)
    {
        $this->deleteScriptFile = $deleteScriptFile;
    }

    /**
     * @return mixed
     */
    public function getScriptFile()
    {
        return $this->scriptFile;
    }

    /**
     * @param mixed $scriptFile
     */
    public function setScriptFile($scriptFile)
    {
        $this->scriptFile = $scriptFile;
    }

    /**
     * @return mixed
     */
    public function getFilesToRemove()
    {
        return $this->filesToRemove;
    }

    /**
     * @param mixed $filesToRemove
     */
    public function setFilesToRemove($filesToRemove)
    {
        $this->filesToRemove = $filesToRemove;
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

