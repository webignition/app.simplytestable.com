<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130607130241_add_AccountPlan_isPremium extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE AccountPlan ADD isPremium TINYINT(1) NOT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE AccountPlan ADD isPremium TINYINT(1) NOT NULL",

        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE AccountPlan DROP isPremium");      
        
        parent::down($schema);
    }     
}
