<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190422180803DropCron extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE CronReport DROP FOREIGN KEY FK_E8516938BE04EA9');
        $this->addSql('DROP TABLE CronJob');
        $this->addSql('DROP TABLE CronReport');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE CronJob (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, command VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, schedule VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, description VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, enabled TINYINT(1) NOT NULL, UNIQUE INDEX un_name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE CronReport (id INT AUTO_INCREMENT NOT NULL, job_id INT DEFAULT NULL, runAt DATETIME NOT NULL, runTime DOUBLE PRECISION NOT NULL, exitCode INT NOT NULL, output LONGTEXT NOT NULL COLLATE utf8_unicode_ci, INDEX IDX_E8516938BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE CronReport ADD CONSTRAINT FK_E8516938BE04EA9 FOREIGN KEY (job_id) REFERENCES CronJob (id)');
    }
}
