<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190404065733DropTaskWorker extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task DROP FOREIGN KEY FK_F24C741B6B20BA36');
        $this->addSql('DROP INDEX IDX_F24C741B6B20BA36 ON Task');
        $this->addSql('ALTER TABLE Task DROP worker_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Task ADD worker_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Task ADD CONSTRAINT FK_F24C741B6B20BA36 FOREIGN KEY (worker_id) REFERENCES Worker (id)');
        $this->addSql('CREATE INDEX IDX_F24C741B6B20BA36 ON Task (worker_id)');
    }
}
