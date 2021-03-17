<?php
namespace App\Migrations;

interface LegacyBackupDirAwareInterface
{
    //backup_dir parameter is not used since 2018 versions, but it is necessary to migrate from older versions
    public function setLegacyBackupDir($backupDir): void;
}

