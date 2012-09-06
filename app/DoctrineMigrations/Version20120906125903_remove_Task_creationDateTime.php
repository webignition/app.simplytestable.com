<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120906125903_remove_Task_creationDateTime extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "DROP INDEX creationDateTime_idx ON Task",
            "ALTER TABLE Task DROP creationDateTime"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Task ADD creationDateTime DATETIME NOT NULL",
            "CREATE INDEX creationDateTime_idx ON Task (creationDateTime)"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"
        );
        
        parent::down($schema);
    }
}
