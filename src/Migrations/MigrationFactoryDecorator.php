<?php
declare(strict_types=1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class MigrationFactoryDecorator implements MigrationFactory
{
    private $migrationFactory;
    private $uploadDir;
    private $backupDir;
    private $container;

    public function __construct(MigrationFactory $migrationFactory, $uploadDir, $backupDir, $container)
    {
        $this->migrationFactory = $migrationFactory;
        $this->uploadDir        = $uploadDir;
        $this->backupDir        = $backupDir;
        $this->container        = $container;
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
        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }
        return $instance;
    }
}
