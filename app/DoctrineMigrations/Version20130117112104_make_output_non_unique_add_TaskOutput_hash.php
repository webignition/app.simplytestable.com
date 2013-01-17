<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130117112104_make_output_non_unique_add_TaskOutput_hash extends BaseMigration
{
    public function up(Schema $schema)
    {     
        
        $this->statements['mysql'] = array(
            "ALTER TABLE Task DROP INDEX UNIQ_F24C741BDE097880, ADD INDEX IDX_F24C741BDE097880 (output_id)",
            "ALTER TABLE TaskOutput ADD hash VARCHAR(32) DEFAULT NULL",
            "CREATE INDEX hash_idx ON TaskOutput (hash)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE INDEX IDX_F24C741BDE097880 ON Task (output_id)",
            "ALTER TABLE TaskOutput ADD hash VARCHAR(32) DEFAULT NULL",
            "CREATE INDEX hash_idx ON TaskOutput (hash)"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {       
        
        $this->statements['mysql'] = array(
            "ALTER TABLE Task DROP INDEX IDX_F24C741BDE097880, ADD UNIQUE INDEX UNIQ_F24C741BDE097880 (output_id)",
            "DROP INDEX hash_idx ON TaskOutput",
            "ALTER TABLE TaskOutput DROP hash"
        );      
        
        $this->statements['sqlite'] = array(
            "ADD UNIQUE INDEX UNIQ_F24C741BDE097880 (output_id)"            
        );
        
        parent::down($schema);
    }
}
