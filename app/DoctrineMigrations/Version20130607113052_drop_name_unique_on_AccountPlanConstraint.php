<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130607113052_drop_name_unique_on_AccountPlanConstraint extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "DROP INDEX UNIQ_E18FF0B75E237E06 ON AccountPlanConstraint"
        );
        
        $this->statements['sqlite'] = array(
            "DROP INDEX UNIQ_E18FF0B75E237E06",

        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("CREATE UNIQUE INDEX UNIQ_E18FF0B75E237E06 ON AccountPlanConstraint (name)");      
        
        parent::down($schema);
    }      
}
