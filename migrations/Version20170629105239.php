<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170629105239 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_2DA17977E7927C74 ON User');
        $this->addSql('ALTER TABLE User CHANGE language language VARCHAR(25) NOT NULL, CHANGE linesperpage linesperpage INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE User CHANGE language language VARCHAR(25) DEFAULT \'en\' NOT NULL COLLATE utf8_general_ci, CHANGE linesperpage linesperpage INT DEFAULT 20 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DA17977E7927C74 ON User (email)');
    }
}
