<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130613120153_add_UserAccountPlan_startTrialPeriod extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->addCommonStatement("ALTER TABLE UserAccountPlan ADD startTrialPeriod INT DEFAULT NULL");  
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE UserAccountPlan DROP startTrialPeriod");      
        
        parent::down($schema);
    }     
}
