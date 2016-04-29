<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160429134626 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A6187E3C61F9');
        $this->addSql('DROP INDEX IDX_C395A6187E3C61F9 ON Job');
        $this->addSql('ALTER TABLE Job DROP owner_id');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Job ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A6187E3C61F9 FOREIGN KEY (owner_id) REFERENCES User (id)');
        $this->addSql('CREATE INDEX IDX_C395A6187E3C61F9 ON Job (owner_id)');
    }
}
