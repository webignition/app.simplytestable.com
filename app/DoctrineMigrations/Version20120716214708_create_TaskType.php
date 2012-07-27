<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214708_create_TaskType extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE TaskType (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F7737B3C5E237E06 (name), PRIMARY KEY(id)) ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE TaskType (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_F7737B3C5E237E06 ON TaskType (name)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {    
        $this->addCommonStatement("DROP TABLE TaskType");      
        
        parent::down($schema);
    }    
}