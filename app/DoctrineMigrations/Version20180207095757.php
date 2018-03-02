<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180207095757 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE BackupLocation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, host VARCHAR(255), directory VARCHAR(255) NOT NULL, tahoe TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Job ADD backupLocation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A618615D27E1 FOREIGN KEY (backupLocation_id) REFERENCES BackupLocation (id)');
        $this->addSql('CREATE INDEX IDX_C395A618615D27E1 ON Job (backupLocation_id)');
        $this->addSql("INSERT INTO BackupLocation VALUES(1,'Default','','/var/spool/elkarbackup/backups',0)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A618615D27E1');
        $this->addSql('DROP TABLE BackupLocation');
        $this->addSql('DROP INDEX IDX_C395A618615D27E1 ON Job');
        $this->addSql('ALTER TABLE Job DROP backupLocation_id');
    }
}
