<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190423083322RemoveTaskTypeClass extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE TaskType DROP FOREIGN KEY FK_F7737B3CAEA19A54');
        $this->addSql('DROP INDEX IDX_F7737B3CAEA19A54 ON TaskType');
        $this->addSql('ALTER TABLE TaskType DROP tasktypeclass_id');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE TaskType ADD tasktypeclass_id INT NOT NULL');
        $this->addSql('ALTER TABLE TaskType 
                                ADD CONSTRAINT FK_F7737B3CAEA19A54 FOREIGN KEY (tasktypeclass_id) 
                                REFERENCES TaskTypeClass (id)'
        );
        $this->addSql('CREATE INDEX IDX_F7737B3CAEA19A54 ON TaskType (tasktypeclass_id)');
    }
}
