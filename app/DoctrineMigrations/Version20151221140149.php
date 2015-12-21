<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151221140149 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX idx_source ON LogRecord');
        $this->addSql('ALTER TABLE Client ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Client ADD CONSTRAINT FK_C0E801637E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('CREATE INDEX IDX_C0E801637E3C61F9 ON Client (owner_id)');
	$this->addSql('UPDATE Client SET owner_id = 1 WHERE owner_id IS NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Client DROP FOREIGN KEY FK_C0E801637E3C61F9');
        $this->addSql('DROP INDEX IDX_C0E801637E3C61F9 ON Client');
        $this->addSql('ALTER TABLE Client DROP owner_id');
        $this->addSql('CREATE INDEX idx_source ON LogRecord (source)');
    }
}
