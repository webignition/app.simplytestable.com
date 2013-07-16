<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130621132138_add_StripeEvent_type_idx extends BaseMigration
{    
    public function up(Schema $schema)
    {  
        $this->addCommonStatement("CREATE INDEX type_idx ON StripeEvent (type)");
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {   
        $this->addCommonStatement("DROP INDEX type_idx ON StripeEvent");        
        parent::down($schema);
    }      
}
