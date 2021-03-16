<?php
namespace App\Migrations;

interface UploadDirAwareInterface
{
    public function setUploadDir(string $uploadDir): void;
}

