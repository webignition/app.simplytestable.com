<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130326155956_add_TimePeriod_indexes extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE INDEX start_idx ON TimePeriod (startDateTime)",
            "CREATE INDEX end_idx ON TimePeriod (endDateTime)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE INDEX start_idx ON TimePeriod (startDateTime)",
            "CREATE INDEX end_idx ON TimePeriod (endDateTime)"         
        );         
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "DROP INDEX start_idx ON TimePeriod",
            "DROP INDEX end_idx ON TimePeriod"
        );
        
        $this->statements['sqlite'] = array(
            "DROP INDEX start_idx ON TimePeriod",
            "DROP INDEX end_idx ON TimePeriod"            
        ); 
        
        parent::up($schema);        
    }  
}
