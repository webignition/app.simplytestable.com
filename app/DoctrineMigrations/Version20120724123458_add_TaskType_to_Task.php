<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724123458_add_TaskType_to_Task extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE Task ADD tasktype_id INT NOT NULL");
        $this->addSql("ALTER TABLE Task ADD CONSTRAINT FK_F24C741B7D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)");
        $this->addSql("CREATE INDEX IDX_F24C741B7D6EFC3 ON Task (tasktype_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE Task DROP FOREIGN KEY FK_F24C741B7D6EFC3");
        $this->addSql("DROP INDEX IDX_F24C741B7D6EFC3 ON Task");
        $this->addSql("ALTER TABLE Task DROP tasktype_id");
    }
}
