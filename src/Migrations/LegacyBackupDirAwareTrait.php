<?php
namespace App\Migrations;

trait LegacyBackupDirAwareTrait
{
    private $backup_dir;

    public function setLegacyBackupDir($backupDir): void
    {
        $this->backup_dir = $backupDir;
    }
}

