<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150311215311_create_ScheduledJob extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ScheduledJob (id INT AUTO_INCREMENT NOT NULL, jobconfiguration_id INT NOT NULL, cronjob_id INT NOT NULL, isRecurring TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_6147474AE5B4855B (jobconfiguration_id), UNIQUE INDEX UNIQ_6147474A6F6F56F3 (cronjob_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ScheduledJob ADD CONSTRAINT FK_6147474AE5B4855B FOREIGN KEY (jobconfiguration_id) REFERENCES JobConfiguration (id)');
        $this->addSql('ALTER TABLE ScheduledJob ADD CONSTRAINT FK_6147474A6F6F56F3 FOREIGN KEY (cronjob_id) REFERENCES CronJob (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ScheduledJob');
    }
}
