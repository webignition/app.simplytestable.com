<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130611095023_add_UserAccountPlan_isActive extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE UserAccountPlan ADD isActive TINYINT(1) DEFAULT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE UserAccountPlan ADD isActive TINYINT(1) DEFAULT NULL",

        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE UserAccountPlan DROP isActive");      
        
        parent::down($schema);
    }      
}
