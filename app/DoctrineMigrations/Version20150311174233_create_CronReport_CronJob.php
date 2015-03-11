<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150311174233_create_CronReport_CronJob extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE CronReport (id INT AUTO_INCREMENT NOT NULL, job_id INT DEFAULT NULL, runAt DATETIME NOT NULL, runTime DOUBLE PRECISION NOT NULL, exitCode INT NOT NULL, output LONGTEXT NOT NULL, INDEX IDX_E8516938BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE CronJob (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, command VARCHAR(255) NOT NULL, schedule VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, UNIQUE INDEX un_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE CronReport ADD CONSTRAINT FK_E8516938BE04EA9 FOREIGN KEY (job_id) REFERENCES CronJob (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE CronReport DROP FOREIGN KEY FK_E8516938BE04EA9');
        $this->addSql('DROP TABLE CronReport');
        $this->addSql('DROP TABLE CronJob');
    }
}
