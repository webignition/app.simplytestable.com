<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130122121234_set_default_Job_type_id extends BaseMigration
{
    public function up(Schema $schema)
    {     
        
        $this->statements['mysql'] = array(
            "UPDATE Job SET type_id = 1"
        );
        
        $this->statements['sqlite'] = array(
            "UPDATE Job SET type_id = 1"          
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "SELECT 1 + 1" 
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"            
        );      
        
        parent::down($schema);
    }
}
