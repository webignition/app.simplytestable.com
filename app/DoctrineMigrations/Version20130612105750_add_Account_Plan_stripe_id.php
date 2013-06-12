<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130612105750_add_Account_Plan_stripe_id extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->addCommonStatement("ALTER TABLE AccountPlan ADD stripe_id VARCHAR(255) DEFAULT NULL");
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE AccountPlan DROP stripe_id");      
        
        parent::down($schema);
    }     
}
