<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;
use App\Migrations\BackupDirAwareTrait;
use App\Migrations\LegacyBackupDirAwareInterface;
use App\Migrations\LegacyBackupDirAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180207095757 extends AbstractMigration implements LegacyBackupDirAwareInterface
{
    use ContainerAwareTrait;
    use LegacyBackupDirAwareTrait;
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('CREATE TABLE BackupLocation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, host VARCHAR(255), directory VARCHAR(255) NOT NULL, tahoe TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Job ADD backupLocation_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_C395A618615D27E1 ON Job (backupLocation_id)');
        
        //get backup_dir param and store as new BackupLocation
        if ($this->backupDir != null) {
            $location = $this->container->getParameter('backup_dir');
            $this->addSql("INSERT INTO BackupLocation VALUES(1,'Default','','" . $location . "',0)");
            
            $rootDir = $this->container->get('kernel')->getRootDir();
            $paramsDir = $rootDir . "/config/parameters.yml";
            $value = Yaml::parse(file_get_contents($paramsDir));
            unset($value['parameters']['backup_dir']);
            $yaml = Yaml::dump($value);
            file_put_contents($paramsDir, $yaml);
            
        } else {
            $this->addSql("INSERT INTO BackupLocation VALUES(1,'Default','', '/var/spool/elkarbackup/backups',0)");
        }
        $this->addSql("UPDATE Job SET backupLocation_id=1");
        $this->addSql('ALTER TABLE Job ADD CONSTRAINT FK_C395A618615D27E1 FOREIGN KEY (backupLocation_id) REFERENCES BackupLocation (id)');
        
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Job DROP FOREIGN KEY FK_C395A618615D27E1');
        $this->addSql('DROP TABLE BackupLocation');
        $this->addSql('DROP INDEX IDX_C395A618615D27E1 ON Job');
        $this->addSql('ALTER TABLE Job DROP backupLocation_id');
    }
}
