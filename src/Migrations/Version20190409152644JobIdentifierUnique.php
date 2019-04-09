<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190409152644JobIdentifierUnique extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE Job CHANGE identifier identifier VARCHAR(255) NOT NULL COLLATE latin1_bin');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C395A618772E836A ON Job (identifier)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP INDEX UNIQ_C395A618772E836A ON Job');
        $this->addSql('ALTER TABLE Job CHANGE identifier identifier VARCHAR(255) DEFAULT NULL COLLATE latin1_bin');
    }
}
