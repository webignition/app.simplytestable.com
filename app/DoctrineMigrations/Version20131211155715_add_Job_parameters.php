<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131211155715_add_Job_parameters extends BaseMigration
{   
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE Job ADD parameters LONGTEXT DEFAULT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE Job ADD parameters LONGTEXT DEFAULT NULL"
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE Job DROP parameters");       
        
        parent::down($schema);
    }    
}
