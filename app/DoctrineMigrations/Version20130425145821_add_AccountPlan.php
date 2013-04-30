<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130425145821_add_AccountPlan extends BaseMigration
{    
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE AccountPlan (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                isVisible TINYINT(1) NOT NULL,
                UNIQUE INDEX UNIQ_F6643B305E237E06 (name),
                PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE AccountPlan (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                isVisible TINYINT(1) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_F6643B305E237E06 ON AccountPlan (name)"
        );
       
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "DROP TABLE AccountPlan",
        );
        
        $this->statements['sqlite'] = array(
            "DROP TABLE AccountPlan"
        );    
        
        parent::down($schema);
    }     
}
