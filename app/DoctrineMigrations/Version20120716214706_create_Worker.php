<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214706_create_Worker extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE Worker (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_981EBA54F47645AE (url), PRIMARY KEY(id)) ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE Worker (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(255) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_981EBA54F47645AE ON Worker (url)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {     
        $this->addCommonStatement("DROP TABLE Worker");     
        
        parent::down($schema);
    }    
}
