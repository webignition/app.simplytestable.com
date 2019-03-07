<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190307102945AddScheduledJobCronJob extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ScheduledJob ADD cronjob_id INT NOT NULL');
        $this->addSql('ALTER TABLE ScheduledJob ADD CONSTRAINT FK_6147474A6F6F56F3 FOREIGN KEY (cronjob_id) REFERENCES cron_job (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6147474A6F6F56F3 ON ScheduledJob (cronjob_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ScheduledJob DROP FOREIGN KEY FK_6147474A6F6F56F3');
        $this->addSql('DROP INDEX UNIQ_6147474A6F6F56F3 ON ScheduledJob');
        $this->addSql('ALTER TABLE ScheduledJob DROP cronjob_id');
    }
}
