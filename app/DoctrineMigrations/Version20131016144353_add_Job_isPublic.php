<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131016144353_add_Job_isPublic extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE Job ADD isPublic INT NOT NULL"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE Job ADD isPublic INT NOT NULL DEFAULT 0"
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("ALTER TABLE Job DROP isPublic");       
        
        parent::down($schema);
    }
}
