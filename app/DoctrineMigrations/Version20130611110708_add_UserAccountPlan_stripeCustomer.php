<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130611110708_add_UserAccountPlan_stripeCustomer extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE UserAccountPlan ADD stripeCustomer VARCHAR(255) DEFAULT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE UserAccountPlan ADD stripeCustomer VARCHAR(255) DEFAULT NULL",

        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE UserAccountPlan DROP stripeCustomer");      
        
        parent::down($schema);
    }     
}
