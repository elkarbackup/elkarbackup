<?php
namespace App\Migrations;

trait UploadDirAwareTrait
{
    private $uploadDir;

    public function setUploadDir(string $uploadDir): void
    {
        $this->uploadDir = $uploadDir;
    }
}

