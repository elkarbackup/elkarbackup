<?php
namespace App\Api\Dto;

class BackupLocationOutput
{
    private $id;
    private $name;
    private $host;
    private $directory;
    private $maxParallelJobs;
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return integer
     */
    public function getMaxParallelJobs()
    {
        return $this->maxParallelJobs;
    }

    /**
     * @param integer $maxParallelJobs
     */
    public function setMaxParallelJobs($maxParallelJobs)
    {
        $this->maxParallelJobs = $maxParallelJobs;
    }
}

