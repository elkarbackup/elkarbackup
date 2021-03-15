<?php
declare(strict_types=1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;

class MigrationFactoryDecorator implements MigrationFactory
{
    private $migrationFactory;
    private $uploadDir;
    private $backupDir;

    public function __construct(MigrationFactory $migrationFactory, $uploadDir, $backupDir)
    {
        $this->migrationFactory = $migrationFactory;
        $this->uploadDir        = $uploadDir;
        $this->backupDir        = $backupDir;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if ($instance instanceof UploadDirAwareInterface) {
            $instance->setUploadDir($this->uploadDir);
        }
        if ($instance instanceof LegacyBackupDirAwareInterface) {
            $instance->setLegacyBackupDir($this->backupDir);
        }

        return $instance;
    }
}
