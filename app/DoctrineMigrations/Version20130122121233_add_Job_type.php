<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130122121233_add_Job_type extends BaseMigration
{
    public function up(Schema $schema)
    {     
        
        $this->statements['mysql'] = array(
            "ALTER TABLE Job ADD type_id INT DEFAULT NULL",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A618C54C8C93 FOREIGN KEY (type_id) REFERENCES JobType (id)",
            "CREATE INDEX IDX_C395A618C54C8C93 ON Job (type_id)"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE Job ADD type_id INT DEFAULT NULL",
            "CREATE INDEX IDX_C395A618C54C8C93 ON Job (type_id)"            
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE Job DROP FOREIGN KEY FK_C395A618C54C8C93",
            "DROP INDEX IDX_C395A618C54C8C93 ON Job",
            "ALTER TABLE Job DROP type_id"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"            
        );      
        
        parent::down($schema);
    }
}
