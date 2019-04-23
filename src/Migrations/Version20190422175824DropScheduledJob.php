<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190422175824DropScheduledJob extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ScheduledJob');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ScheduledJob (id INT AUTO_INCREMENT NOT NULL, jobconfiguration_id INT NOT NULL, cronjob_id INT NOT NULL, isRecurring TINYINT(1) NOT NULL, cronModifier VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, UNIQUE INDEX UNIQ_6147474A6F6F56F3 (cronjob_id), INDEX IDX_6147474AE5B4855B (jobconfiguration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ScheduledJob ADD CONSTRAINT FK_6147474A6F6F56F3 FOREIGN KEY (cronjob_id) REFERENCES CronJob (id)');
        $this->addSql('ALTER TABLE ScheduledJob ADD CONSTRAINT FK_6147474AE5B4855B FOREIGN KEY (jobconfiguration_id) REFERENCES JobConfiguration (id)');
    }
}
