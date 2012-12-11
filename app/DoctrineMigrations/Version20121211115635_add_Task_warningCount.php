<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121211115635_add_Task_warningCount extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE TaskOutput ADD warningCount INT DEFAULT 0 NOT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE TaskOutput ADD warningCount INT DEFAULT 0 NOT NULL"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE TaskOutput DROP warningCount"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"
        );
        
        parent::down($schema);
    }
}
