<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180312114527 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE Queue (id INT AUTO_INCREMENT NOT NULL, job_id INT DEFAULT NULL, date DATETIME NOT NULL, runningSince DATETIME NULL, INDEX IDX_BE3C5067BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Queue ADD CONSTRAINT FK_BE3C5067BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE Job DROP status');
        $this->addSql('ALTER TABLE Job ADD lastResult VARCHAR(10) DEFAULT NULL');
   }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE Queue');
        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A61817EE0EA');
        $this->addSql('ALTER TABLE Job DROP lastResult');
        $this->addSql('ALTER TABLE Job ADD status VARCHAR(255) DEFAULT NULL');
    }
}
