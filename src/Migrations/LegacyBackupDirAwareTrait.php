<?php
namespace App\Migrations;

trait LegacyBackupDirAwareTrait
{
    private $backupDir;

    public function setLegacyBackupDir($backupDir): void
    {
        $this->backupDir = $backupDir;
    }
}

