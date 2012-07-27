<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214711_create_TaskTypeClass extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE TaskTypeClass (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                UNIQUE INDEX UNIQ_F92FE5F25E237E06 (name),
                PRIMARY KEY(id)) ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE TaskTypeClass (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL)",
            "CREATE UNIQUE INDEX UNIQ_F92FE5F25E237E06 ON TaskTypeClass (name)"
        );
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {    
        $this->addCommonStatement("DROP TABLE TaskTypeClass");      
        
        parent::down($schema);
    }
    
}
