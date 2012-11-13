<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121113145729_add_Task_parameters extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Task ADD parameters LONGTEXT DEFAULT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE Task ADD parameters LONGTEXT DEFAULT NULL"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Task DROP parameters"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"
        );
        
        parent::down($schema);
    }
}
