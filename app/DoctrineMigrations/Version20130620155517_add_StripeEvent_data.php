<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;


/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130620155517_add_StripeEvent_data extends BaseMigration
{
    
    public function up(Schema $schema)
    {        
        $this->addCommonStatement("ALTER TABLE StripeEvent ADD data LONGTEXT DEFAULT NULL");  
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE StripeEvent DROP data");      
        
        parent::down($schema);
    }      
}
