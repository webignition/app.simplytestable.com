<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120727163917_untranspose_JobTaskType_primary_key_field_constraints extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE JobTaskTypes DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE JobTaskTypes ADD PRIMARY KEY (job_id, tasktype_id)");
    }

    public function down(Schema $schema)
    {
        // this down() migration is autogenerated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        
        $this->addSql("ALTER TABLE JobTaskTypes DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE JobTaskTypes ADD PRIMARY KEY (tasktype_id, job_id)");
    }
}
