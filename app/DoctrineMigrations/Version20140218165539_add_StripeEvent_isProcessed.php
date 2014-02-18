<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140218165539_add_StripeEvent_isProcessed extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->addCommonStatement("ALTER TABLE StripeEvent ADD isProcessed TINYINT(1) DEFAULT '0' NOT NULL");
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE StripeEvent DROP isProcessed");       
        
        parent::down($schema);
    }
}
