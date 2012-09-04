<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120904172837_add_Task_creationDateTime extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Task ADD creationDateTime DATETIME NOT NULL",
            "CREATE INDEX creationDateTime_idx ON Task (creationDateTime)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE INDEX creationDateTime_idx ON Task (creationDateTime)"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Task DROP creationDateTime"
        );
        
        $this->statements['sqlite'] = array();
        
        $this->addCommonStatement("DROP INDEX creationDateTime_idx ON Task");
        
        parent::down($schema);
    }
}
