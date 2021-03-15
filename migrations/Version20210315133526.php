<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210315133526 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE BackupLocation DROP tahoe, CHANGE maxParallelJobs maxParallelJobs INT NOT NULL');
        $this->addSql('ALTER TABLE Client CHANGE maxParallelJobs maxParallelJobs INT NOT NULL');
        $this->addSql('ALTER TABLE Job CHANGE backupLocation_id backupLocation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Job RENAME INDEX idx_c395a618615d27e1 TO IDX_C395A61817EE0EA');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE BackupLocation ADD tahoe TINYINT(1) NOT NULL, CHANGE maxParallelJobs maxParallelJobs INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE Client CHANGE maxParallelJobs maxParallelJobs INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE Job CHANGE backupLocation_id backupLocation_id INT NOT NULL');
        $this->addSql('ALTER TABLE Job RENAME INDEX idx_c395a61817ee0ea TO IDX_C395A618615D27E1');
    }
}
