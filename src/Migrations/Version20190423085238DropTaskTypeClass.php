<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190423085238DropTaskTypeClass extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP TABLE TaskTypeClass');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('CREATE TABLE TaskTypeClass (
                                id INT AUTO_INCREMENT NOT NULL, 
                                name VARCHAR(255) NOT NULL COLLATE latin1_swedish_ci, 
                                description LONGTEXT DEFAULT NULL COLLATE latin1_swedish_ci, 
                                UNIQUE INDEX UNIQ_F92FE5F25E237E06 (name), 
                                PRIMARY KEY(id)
                            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
    }
}
